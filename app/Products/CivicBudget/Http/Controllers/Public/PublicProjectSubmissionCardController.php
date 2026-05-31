<?php

namespace App\Products\CivicBudget\Http\Controllers\Public;

use App\Products\CivicBudget\Domain\Projects\Enums\ProjectStatus;
use App\Products\CivicBudget\Domain\Projects\Models\Project;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PublicProjectSubmissionCardController extends Controller
{
    public function __invoke(Request $request, Project $project): Response
    {
        Log::info('project.submission_card_pdf.start', [
            'project_id' => $project->id,
            'actor_id' => $request->user()->id,
            'status' => $project->status->value,
        ]);

        if ($project->creator_id !== $request->user()->id) {
            Log::warning('project.submission_card_pdf.rejected', [
                'project_id' => $project->id,
                'actor_id' => $request->user()->id,
                'reason' => 'not_creator',
            ]);

            abort(403);
        }

        if ($project->status === ProjectStatus::WorkingCopy || $project->submitted_at === null) {
            Log::warning('project.submission_card_pdf.rejected', [
                'project_id' => $project->id,
                'actor_id' => $request->user()->id,
                'reason' => 'not_submitted',
            ]);

            abort(404);
        }

        $project->load([
            'area',
            'budgetEdition',
            'category',
            'categories',
            'coauthors',
            'costItems',
            'files',
        ]);

        $pdf = Pdf::loadView('public.projects.submission-card-pdf', [
            'project' => $project,
        ])->setPaper('a4');

        Log::info('project.submission_card_pdf.success', [
            'project_id' => $project->id,
            'actor_id' => $request->user()->id,
        ]);

        return $pdf->download($this->filename($project));
    }

    private function filename(Project $project): string
    {
        $number = $project->number_drawn ?? $project->number ?? $project->id;
        $safeNumber = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $number) ?: (string) $project->id;

        return "karta-zgloszeniowa-projekt-{$safeNumber}.pdf";
    }
}
