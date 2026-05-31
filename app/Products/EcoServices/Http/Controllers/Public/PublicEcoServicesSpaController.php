<?php

namespace App\Products\EcoServices\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Platform\Clients\Services\CurrentClient;
use App\Products\EcoServices\Domain\Address\Models\ResidentAddress;
use App\Products\EcoServices\Domain\AirQuality\Services\AirQualitySnapshotService;
use App\Products\EcoServices\Domain\News\Models\NewsPost;
use App\Products\EcoServices\Domain\Pszok\Models\PszokPoint;
use App\Products\EcoServices\Domain\Schedule\Services\UpcomingCollectionService;
use App\Products\EcoServices\Domain\Waste\Models\WasteFraction;
use App\Products\EcoServices\Http\Controllers\EcoServicesPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicEcoServicesSpaController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentClient $currentClient,
        EcoServicesPayload $payload,
        AirQualitySnapshotService $airQuality,
        UpcomingCollectionService $upcomingCollectionService,
    ): View {
        $client = $currentClient->require();

        Log::info('eco_services.public_spa.show.start', [
            'client_id' => $client->id,
            'path' => $request->path(),
        ]);

        $addresses = $request->user()
            ? ResidentAddress::query()
                ->with('zone')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('is_active')
                ->latest()
                ->get()
            : collect();
        $activeAddress = $addresses->firstWhere('is_active', true);

        $upcoming = $activeAddress instanceof ResidentAddress
            ? $upcomingCollectionService->forAddress($activeAddress, 8)
            : collect();

        $news = NewsPost::query()
            ->with('category')
            ->published()
            ->latest('published_at')
            ->limit(8)
            ->get();

        $spaState = [
            'platform' => [
                'currentClient' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'slug' => $client->slug,
                ],
                'enabledProducts' => $client->products()
                    ->enabled()
                    ->get()
                    ->map(fn ($product): array => [
                        'key' => $product->product_key->value,
                        'label' => $product->product_key->label(),
                    ])
                    ->values()
                    ->all(),
            ],
            'app' => [
                'title' => 'Ekousługi',
                'currentPath' => $request->getPathInfo(),
                'csrfToken' => csrf_token(),
                'flash' => session('status'),
                'errors' => $request->session()->get('errors')?->getMessages() ?? [],
                'old' => $request->session()->getOldInput(),
                'authenticated' => $request->user() !== null,
                'userId' => $request->user()?->id,
            ],
            'links' => [
                'home' => route('public.home'),
                'projects' => route('public.projects.index'),
                'announcements' => route('public.announcements.index'),
                'residentDashboard' => route('public.resident.dashboard'),
                'residentProjects' => route('public.resident.projects'),
                'residentAccount' => route('public.resident.account'),
                'residentSubmit' => route('public.resident.projects.create'),
                'ecoHome' => route('eco-services.home'),
                'ecoSchedule' => route('eco-services.schedule'),
                'ecoSegregation' => route('eco-services.segregation'),
                'ecoWasteSearch' => route('eco-services.waste-search'),
                'ecoPszok' => route('eco-services.pszok'),
                'ecoAirQuality' => route('eco-services.air-quality'),
                'ecoNews' => route('eco-services.news.index'),
                'ecoAddresses' => route('eco-services.addresses'),
                'ecoAddressStore' => route('eco-services.addresses.store'),
                'ecoRecognize' => route('eco-services.waste.recognize'),
                'login' => route('login'),
                'logout' => route('public.resident.logout'),
                'admin' => url('/admin/eco-services'),
            ],
            'projects' => [],
            'announcements' => [],
            'pages' => [],
            'ecoServices' => [
                'addresses' => $addresses->map(fn (ResidentAddress $address): array => $payload->address($address))->values()->all(),
                'activeAddressId' => $activeAddress?->id,
                'upcomingCollections' => $upcoming->map(fn ($date): array => $payload->scheduleDate($date))->values()->all(),
                'fractions' => WasteFraction::query()->active()->orderBy('name')->get()
                    ->map(fn (WasteFraction $fraction): array => $payload->fraction($fraction))
                    ->values()
                    ->all(),
                'pszokPoints' => PszokPoint::query()->with('fractions')->active()->orderBy('name')->get()
                    ->map(fn (PszokPoint $point): array => $payload->pszok($point))
                    ->values()
                    ->all(),
                'airQualityStations' => $airQuality->stations()
                    ->map(fn ($station): array => $payload->airStation($station))
                    ->values()
                    ->all(),
                'news' => $news->map(fn (NewsPost $post): array => $payload->newsPost($post))->values()->all(),
            ],
        ];

        Log::info('eco_services.public_spa.show.success', [
            'client_id' => $client->id,
            'addresses_count' => $addresses->count(),
            'news_count' => $news->count(),
        ]);

        return view('public.spa', [
            'spaState' => $spaState,
        ]);
    }
}
