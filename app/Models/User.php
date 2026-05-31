<?php

namespace App\Models;

use App\Notifications\ResidentEmailVerification;
use App\Notifications\ResidentResetPassword;
use App\Platform\Clients\Models\Client;
use App\Platform\Clients\Models\ClientMembership;
use App\Platform\Clients\Services\CurrentClient;
use App\Platform\Products\Enums\ProductKey;
use App\Platform\Users\Models\Department;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'legacy_id',
    'status',
    'pesel',
    'first_name',
    'last_name',
    'phone',
    'street',
    'house_no',
    'flat_no',
    'post_code',
    'city',
    'department_id',
    'department_text',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status !== true) {
            return false;
        }

        $client = app(CurrentClient::class)->resolveDefault();

        if (! $client instanceof Client || ! $this->canUseClient($client)) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->can('platform.admin.access')
                || $this->can('platform.users.manage')
                || $this->can('admin.access')
                || $this->hasAnyRole(['admin', 'bdo']),
            ProductKey::CivicBudget->adminPanelId() => $client->isProductEnabled(ProductKey::CivicBudget)
                && (
                    $this->can('civic_budget.admin.access')
                    || $this->can('admin.access')
                    || $this->hasAnyRole(['admin', 'bdo'])
                ),
            ProductKey::EkoUslugi->adminPanelId() => $client->isProductEnabled(ProductKey::EkoUslugi)
                && (
                    $this->can('eko_uslugi.admin.access')
                    || $this->can('admin.access')
                    || $this->hasAnyRole(['admin', 'bdo'])
                ),
            default => false,
        };
    }

    public function sendEmailVerificationNotification(): void
    {
        Notification::send($this, new ResidentEmailVerification);
    }

    public function sendPasswordResetNotification(#[SensitiveParameter] $token): void
    {
        Notification::send($this, new ResidentResetPassword($token));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function clientMemberships(): HasMany
    {
        return $this->hasMany(ClientMembership::class);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_memberships')
            ->withPivot(['is_active'])
            ->withTimestamps();
    }

    public function ensureClientMembership(Client $client): void
    {
        $this->clientMemberships()->firstOrCreate([
            'client_id' => $client->id,
        ], [
            'is_active' => true,
        ]);
    }

    private function canUseClient(Client $client): bool
    {
        if ($this->clientMemberships()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->exists()) {
            return true;
        }

        return $client->slug === Client::DEFAULT_SLUG
            && ($this->roles()->exists() || $this->permissions()->exists());
    }
}
