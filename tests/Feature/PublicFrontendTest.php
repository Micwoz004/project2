<?php

use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectArea;
use App\Products\CivicBudget\Domain\Projects\Models\ProjectCorrection;
use App\Products\CivicBudget\Domain\Settings\Models\CostGuideItem;
use App\Products\CivicBudget\Domain\Settings\Models\PublicAnnouncement;
use App\Products\CivicBudget\Domain\Settings\Models\PublicPage;
use App\Platform\Users\Actions\SyncSystemRolesAndPermissionsAction;
use App\Platform\Users\Enums\SystemPermission;
use App\Products\CivicBudget\Filament\Resources\CostGuideItems\CostGuideItemResource;
use App\Products\CivicBudget\Filament\Resources\PublicAnnouncements\PublicAnnouncementResource;
use App\Products\CivicBudget\Filament\Resources\PublicPages\PublicPageResource;
use App\Models\User;
use App\Notifications\ResidentEmailVerification;
use App\Notifications\ResidentResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

it('renders public homepage with current edition schedule and published announcements', function (): void {
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    project($edition->id, $area->id, [
        'title' => 'Skwer sąsiedzki',
        'status' => ProjectStatus::Submitted,
    ]);
    PublicAnnouncement::query()->create([
        'title' => 'Start naboru projektów',
        'slug' => 'start-naboru',
        'lead' => 'Można zgłaszać projekty do nowej edycji.',
        'body' => '<p>Treść ogłoszenia.</p>',
        'published_at' => now()->subHour(),
        'is_published' => true,
    ]);

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('window.BO_SPA', false)
        ->assertSee('Start naboru projektów')
        ->assertSee('Projekty');
});

it('renders cost guide items from admin content', function (): void {
    CostGuideItem::query()->create([
        'label' => 'Kosz parkowy premium',
        'price_range' => '3-8 tys. zł',
        'is_published' => true,
        'sort' => 10,
    ]);
    CostGuideItem::query()->create([
        'label' => 'Robocza pozycja',
        'price_range' => '1 zł',
        'is_published' => false,
        'sort' => 20,
    ]);

    $response = $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('Kosz parkowy premium')
        ->assertDontSee('Robocza pozycja');

    expect($response->getContent())->toContain('3-8 tys. z\\u0142');
});

it('shows only published public announcements', function (): void {
    PublicAnnouncement::query()->create([
        'title' => 'Widoczne ogłoszenie',
        'slug' => 'widoczne-ogloszenie',
        'body' => '<p>Treść widoczna.</p>',
        'published_at' => now()->subDay(),
        'is_published' => true,
    ]);
    PublicAnnouncement::query()->create([
        'title' => 'Robocze ogłoszenie',
        'slug' => 'robocze-ogloszenie',
        'body' => '<p>Treść robocza.</p>',
        'published_at' => now()->subDay(),
        'is_published' => false,
    ]);

    $this->get(route('public.announcements.index'))
        ->assertOk()
        ->assertSee('Widoczne ogłoszenie')
        ->assertDontSee('Robocze ogłoszenie');

    $this->get(route('public.announcements.show', 'robocze-ogloszenie'))
        ->assertNotFound();
});

it('uses database public page over static fallback', function (): void {
    PublicPage::query()->create([
        'title' => 'Harmonogram z panelu',
        'slug' => 'harmonogram',
        'body' => '<p>Treść z bazy danych.</p>',
        'is_published' => true,
    ]);

    $this->get(route('public.info.show', 'harmonogram'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('Harmonogram z panelu')
        ->assertDontSee('Dokładne daty są prezentowane');
});

it('renders static information fallback when public page is not configured', function (): void {
    $this->get(route('public.info.show', 'o-budzecie'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('window.BO_SPA', false);
});

it('guards public content resources with settings permissions', function (): void {
    app(SyncSystemRolesAndPermissionsAction::class)->execute();

    PublicAnnouncement::query()->create([
        'title' => 'Ogłoszenie admin',
        'slug' => 'ogloszenie-admin',
        'body' => '<p>Treść.</p>',
        'is_published' => true,
    ]);
    PublicPage::query()->create([
        'title' => 'Strona admin',
        'slug' => 'strona-admin',
        'body' => '<p>Treść.</p>',
        'is_published' => true,
    ]);
    CostGuideItem::query()->create([
        'label' => 'Pozycja admin',
        'price_range' => '1-2 tys. zł',
        'is_published' => true,
    ]);

    $manager = User::factory()->create(['status' => true]);
    $manager->givePermissionTo(SystemPermission::AdminAccess->value);
    $manager->givePermissionTo(SystemPermission::SettingsManage->value);
    $viewer = User::factory()->create(['status' => true]);
    $viewer->givePermissionTo(SystemPermission::AdminAccess->value);

    $this->actingAs($manager)
        ->get(PublicAnnouncementResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Ogłoszenie admin');

    $this->actingAs($manager)
        ->get(PublicPageResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Strona admin');

    $this->actingAs($manager)
        ->get(CostGuideItemResource::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee('Pozycja admin');

    $this->actingAs($viewer)
        ->get(PublicAnnouncementResource::getUrl(panel: 'admin'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(PublicPageResource::getUrl(panel: 'admin'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(CostGuideItemResource::getUrl(panel: 'admin'))
        ->assertForbidden();
});

it('renders logged resident dashboard and project list from owned projects', function (): void {
    $user = User::factory()->create([
        'status' => true,
        'first_name' => 'Anna',
        'last_name' => 'Kowalska',
    ]);
    $edition = budgetEdition();
    $area = ProjectArea::query()->create(areaAttributes());
    $project = project($edition->id, $area->id, [
        'creator_id' => $user->id,
        'title' => 'Park z korektą',
        'status' => ProjectStatus::Submitted,
    ]);
    ProjectCorrection::query()->create([
        'project_id' => $project->id,
        'allowed_fields' => ['description'],
        'notes' => 'Uzupełnij opis lokalizacji.',
        'correction_deadline' => now()->addDays(7),
        'correction_done' => false,
    ]);

    $this->actingAs($user)
        ->get(route('public.resident.dashboard'))
        ->assertOk()
        ->assertSee('Park z korekt\\u0105', false)
        ->assertSee('Uzupe\\u0142nij opis lokalizacji.', false)
        ->assertSee('"corrections":1', false);

    $this->actingAs($user)
        ->get(route('public.resident.projects'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('Park z korekt\\u0105', false);
});

it('updates resident account data and password from public account form', function (): void {
    $user = User::factory()->create([
        'status' => true,
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user)
        ->patch(route('public.resident.account.update'), [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'email' => 'anna.nowak@example.test',
            'phone' => '500600700',
            'street' => 'Miejska',
            'house_no' => '12',
            'flat_no' => '4',
            'post_code' => '12-345',
            'city' => 'Miasto',
            'current_password' => 'password',
            'password' => 'Nowe-Bezpieczne-Haslo-123!',
            'password_confirmation' => 'Nowe-Bezpieczne-Haslo-123!',
        ])
        ->assertRedirect(route('public.resident.account'))
        ->assertSessionHas('status', 'Dane konta zostały zapisane.');

    $user->refresh();

    expect($user->name)->toBe('Anna Nowak')
        ->and($user->email)->toBe('anna.nowak@example.test')
        ->and($user->city)->toBe('Miasto')
        ->and(Hash::check('Nowe-Bezpieczne-Haslo-123!', $user->password))->toBeTrue();
});

it('logs regular resident into public resident panel without admin permission', function (): void {
    $user = User::factory()->create([
        'status' => true,
        'email' => 'resident@example.test',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('public.resident.login'), [
        'email' => 'resident@example.test',
        'password' => 'password',
    ])
        ->assertRedirect(route('public.resident.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('renders guest resident account entry screens as SPA routes', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('rejestracja')
        ->assertSee('haslo\\/reset', false);

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('rejestracja');

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('bo-spa-root')
        ->assertSee('haslo\\/reset', false);
});

it('registers resident from public registration form', function (): void {
    Notification::fake();

    $this->post(route('public.resident.register'), [
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'email' => 'jan.kowalski@example.test',
        'password' => 'Bezpieczne-Haslo-Testowe-123!',
        'password_confirmation' => 'Bezpieczne-Haslo-Testowe-123!',
    ])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'Konto mieszkańca zostało utworzone. Wysłaliśmy link weryfikacyjny na podany adres e-mail.');

    $user = User::query()->where('email', 'jan.kowalski@example.test')->firstOrFail();

    expect($user->first_name)->toBe('Jan')
        ->and($user->last_name)->toBe('Kowalski')
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('Bezpieczne-Haslo-Testowe-123!', $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, ResidentEmailVerification::class, function (ResidentEmailVerification $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        view($mail->view['html'], $mail->viewData)->render();

        return ($mail->view['html'] ?? null) === 'mail.resident-email-verification'
            && ($mail->view['text'] ?? null) === 'mail.resident-email-verification-text'
            && isset($mail->viewData['privacyUrl'], $mail->viewData['accessibilityUrl']);
    });
});

it('rejects weak resident registration password', function (): void {
    $this->post(route('public.resident.register'), [
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'email' => 'jan.weak@example.test',
        'password' => 'slabehaslo',
        'password_confirmation' => 'slabehaslo',
    ])
        ->assertSessionHasErrors('password');

    expect(User::query()->where('email', 'jan.weak@example.test')->exists())->toBeFalse();
});

it('does not log inactive residents into public resident panel', function (): void {
    User::factory()->create([
        'status' => false,
        'email' => 'inactive@example.test',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('public.resident.login'), [
        'email' => 'inactive@example.test',
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('confirms resident email from signed verification link', function (): void {
    $user = User::factory()->unverified()->create(['status' => true]);
    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('public.resident.dashboard'))
        ->assertSessionHas('status', 'Adres e-mail został potwierdzony.');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('renders flash status below resident header after registration redirect', function (): void {
    $user = User::factory()->create(['status' => true]);

    $this->actingAs($user)
        ->withSession(['status' => 'Konto mieszkańca zostało utworzone.'])
        ->get(route('public.resident.dashboard'))
        ->assertOk()
        ->assertSee('window.BO_SPA', false)
        ->assertSee('Konto mieszka\\u0144ca zosta\\u0142o utworzone.', false);
});

it('sends resident password reset link and updates password with token', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'resident-reset@example.test',
        'password' => Hash::make('old-password'),
        'status' => true,
    ]);

    $this->post(route('password.email'), [
        'email' => 'resident-reset@example.test',
    ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Jeśli konto istnieje, wyślemy wiadomość z linkiem do ustawienia nowego hasła.');

    Notification::assertSentTo($user, ResidentResetPassword::class, function (ResidentResetPassword $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        view($mail->view['html'], $mail->viewData)->render();

        return ($mail->view['html'] ?? null) === 'mail.resident-password-reset'
            && ($mail->view['text'] ?? null) === 'mail.resident-password-reset-text'
            && isset($mail->viewData['privacyUrl'], $mail->viewData['accessibilityUrl']);
    });

    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'resident-reset@example.test',
        'password' => 'Nowe-Haslo-Reset-123!',
        'password_confirmation' => 'Nowe-Haslo-Reset-123!',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Hasło zostało zmienione. Możesz się zalogować.');

    expect(Hash::check('Nowe-Haslo-Reset-123!', $user->refresh()->password))->toBeTrue();
});
