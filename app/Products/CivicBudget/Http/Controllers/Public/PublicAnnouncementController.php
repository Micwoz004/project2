<?php

namespace App\Products\CivicBudget\Http\Controllers\Public;

use App\Products\CivicBudget\Domain\Settings\Models\PublicAnnouncement;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicAnnouncementController extends Controller
{
    public function index(): View
    {
        Log::info('public_announcements.index.start');

        $announcements = PublicAnnouncement::query()
            ->published()
            ->latest('published_at')
            ->paginate(9);

        Log::info('public_announcements.index.success', [
            'count' => $announcements->count(),
        ]);

        return view('public.announcements.index', [
            'announcements' => $announcements,
        ]);
    }

    public function show(string $slug): View
    {
        Log::info('public_announcements.show.start', [
            'slug' => $slug,
        ]);

        $announcement = PublicAnnouncement::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        Log::info('public_announcements.show.success', [
            'announcement_id' => $announcement->id,
        ]);

        return view('public.announcements.show', [
            'announcement' => $announcement,
        ]);
    }
}
