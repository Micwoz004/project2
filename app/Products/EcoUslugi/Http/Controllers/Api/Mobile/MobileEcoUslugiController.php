<?php

namespace App\Products\EcoUslugi\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Products\EcoUslugi\Domain\Address\Models\ResidentAddress;
use App\Products\EcoUslugi\Domain\Address\Services\AddressZoneMatcher;
use App\Products\EcoUslugi\Domain\AirQuality\Services\AirQualitySnapshotService;
use App\Products\EcoUslugi\Domain\News\Models\NewsPost;
use App\Products\EcoUslugi\Domain\Pszok\Models\PszokPoint;
use App\Products\EcoUslugi\Domain\Schedule\Services\UpcomingCollectionService;
use App\Products\EcoUslugi\Domain\Waste\Models\WasteFraction;
use App\Products\EcoUslugi\Domain\Waste\Services\WasteRecognitionService;
use App\Products\EcoUslugi\Domain\Waste\Services\WasteSearchService;
use App\Products\EcoUslugi\Http\Controllers\EcoUslugiPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobileEcoUslugiController extends Controller
{
    public function overview(EcoUslugiPayload $payload, AirQualitySnapshotService $airQuality): JsonResponse
    {
        Log::info('eco_uslugi.mobile.overview.start');

        $response = [
            'fractions' => WasteFraction::query()->active()->orderBy('name')->limit(8)->get()
                ->map(fn (WasteFraction $fraction): array => $payload->fraction($fraction))
                ->values()
                ->all(),
            'news' => NewsPost::query()->with('category')->published()->latest('published_at')->limit(3)->get()
                ->map(fn (NewsPost $post): array => $payload->newsPost($post))
                ->values()
                ->all(),
            'airQualityStations' => $airQuality->stations()
                ->take(3)
                ->map(fn ($station): array => $payload->airStation($station))
                ->values()
                ->all(),
        ];

        Log::info('eco_uslugi.mobile.overview.success');

        return response()->json($response);
    }

    public function fractions(EcoUslugiPayload $payload): JsonResponse
    {
        return response()->json([
            'items' => WasteFraction::query()->active()->orderBy('name')->get()
                ->map(fn (WasteFraction $fraction): array => $payload->fraction($fraction))
                ->values()
                ->all(),
        ]);
    }

    public function searchWaste(Request $request, WasteSearchService $searchService, EcoUslugiPayload $payload): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:150'],
        ]);

        return response()->json([
            'items' => $searchService->search($validated['query'])
                ->map(fn ($item): array => $payload->wasteItem($item))
                ->values()
                ->all(),
        ]);
    }

    public function recognizeWaste(Request $request, WasteRecognitionService $recognitionService): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'max:4'],
            'files.*' => ['file', 'image', 'max:8192'],
        ]);

        return response()->json([
            'results' => $recognitionService->recognize($validated['files']),
        ]);
    }

    public function pszok(EcoUslugiPayload $payload): JsonResponse
    {
        return response()->json([
            'items' => PszokPoint::query()->with('fractions')->active()->orderBy('name')->get()
                ->map(fn (PszokPoint $point): array => $payload->pszok($point))
                ->values()
                ->all(),
        ]);
    }

    public function pszokPoint(PszokPoint $point, EcoUslugiPayload $payload): JsonResponse
    {
        abort_unless($point->status === 'active', 404);

        return response()->json([
            'item' => $payload->pszok($point->load('fractions')),
        ]);
    }

    public function news(EcoUslugiPayload $payload): JsonResponse
    {
        return response()->json([
            'items' => NewsPost::query()->with('category')->published()->latest('published_at')->paginate(12)
                ->through(fn (NewsPost $post): array => $payload->newsPost($post)),
        ]);
    }

    public function newsPost(NewsPost $post, EcoUslugiPayload $payload): JsonResponse
    {
        abort_unless($post->status === 'published' && ($post->published_at === null || $post->published_at->lte(now())), 404);

        return response()->json([
            'item' => $payload->newsPost($post->load('category')),
        ]);
    }

    public function airQuality(EcoUslugiPayload $payload, AirQualitySnapshotService $airQuality): JsonResponse
    {
        return response()->json([
            'items' => $airQuality->stations()
                ->map(fn ($station): array => $payload->airStation($station))
                ->values()
                ->all(),
        ]);
    }

    public function addresses(Request $request, EcoUslugiPayload $payload): JsonResponse
    {
        return response()->json([
            'items' => ResidentAddress::query()
                ->with('zone')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('is_active')
                ->latest()
                ->get()
                ->map(fn (ResidentAddress $address): array => $payload->address($address))
                ->values()
                ->all(),
        ]);
    }

    public function storeAddress(Request $request, AddressZoneMatcher $matcher, EcoUslugiPayload $payload): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'building_type' => ['nullable', 'string', 'max:30'],
            'locality' => ['required', 'string', 'max:150'],
            'street' => ['nullable', 'string', 'max:180'],
            'building_number' => ['required', 'string', 'max:20'],
            'apartment_number' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['nullable', 'string', 'max:12'],
        ]);

        $address = ResidentAddress::query()->create([
            ...$validated,
            'user_id' => $request->user()->id,
            'is_active' => ! ResidentAddress::query()->where('user_id', $request->user()->id)->exists(),
        ]);
        $zone = $matcher->match($address);
        $address->forceFill(['eco_zone_id' => $zone?->id])->save();

        return response()->json([
            'item' => $payload->address($address->refresh()->load('zone')),
        ], 201);
    }

    public function upcoming(Request $request, UpcomingCollectionService $upcomingCollectionService, EcoUslugiPayload $payload): JsonResponse
    {
        $address = ResidentAddress::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        if (! $address instanceof ResidentAddress) {
            Log::warning('eco_uslugi.mobile.upcoming.rejected_missing_active_address', [
                'user_id' => $request->user()->id,
            ]);

            return response()->json(['items' => []]);
        }

        return response()->json([
            'items' => $upcomingCollectionService->forAddress($address)
                ->map(fn ($date): array => $payload->scheduleDate($date))
                ->values()
                ->all(),
        ]);
    }
}
