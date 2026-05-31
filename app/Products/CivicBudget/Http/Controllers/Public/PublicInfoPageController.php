<?php

namespace App\Products\CivicBudget\Http\Controllers\Public;

use App\Products\CivicBudget\Domain\Settings\Models\PublicPage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicInfoPageController extends Controller
{
    public function show(string $slug): View
    {
        Log::info('public_info_page.show.start', [
            'slug' => $slug,
        ]);

        $page = PublicPage::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        $fallback = $page instanceof PublicPage ? null : $this->fallbackPage($slug);

        if (! $page instanceof PublicPage && $fallback === null) {
            Log::warning('public_info_page.show.rejected_not_found', [
                'slug' => $slug,
            ]);

            throw new NotFoundHttpException;
        }

        Log::info('public_info_page.show.success', [
            'slug' => $slug,
            'page_id' => $page?->id,
            'fallback' => $fallback !== null,
        ]);

        return view('public.info.show', [
            'title' => $page?->title ?? $fallback['title'],
            'body' => $page?->body ?? $fallback['body'],
            'slug' => $slug,
        ]);
    }

    /**
     * @return array{title: string, body: string}|null
     */
    private function fallbackPage(string $slug): ?array
    {
        return match ($slug) {
            'o-budzecie' => [
                'title' => 'O budżecie obywatelskim',
                'body' => '<p>Budżet obywatelski to proces, w którym mieszkanki i mieszkańcy zgłaszają projekty, a następnie decydują w głosowaniu, które pomysły zostaną skierowane do realizacji.</p><p>W serwisie można sprawdzić harmonogram edycji, zgłosić projekt, przejrzeć listę propozycji i oddać głos w aktywnym terminie.</p>',
            ],
            'harmonogram' => [
                'title' => 'Harmonogram',
                'body' => '<p>Proces obejmuje zgłaszanie projektów, weryfikację, publikację listy do głosowania, głosowanie mieszkańców oraz publikację wyników.</p><p>Dokładne daty są prezentowane na stronie startowej na podstawie skonfigurowanej edycji budżetu.</p>',
            ],
            default => null,
        };
    }
}
