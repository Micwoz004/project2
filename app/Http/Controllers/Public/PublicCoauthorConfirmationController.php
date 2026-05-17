<?php

namespace App\Http\Controllers\Public;

use App\Domain\Projects\Actions\ConfirmProjectCoauthorAction;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PublicCoauthorConfirmationController extends Controller
{
    public function __invoke(Request $request, ConfirmProjectCoauthorAction $confirmProjectCoauthor): Response
    {
        Log::info('public.coauthor_confirmation.start');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:127'],
            'hash' => ['required', 'string', 'max:255'],
        ]);

        try {
            $coauthor = $confirmProjectCoauthor->execute($data['email'], $data['hash']);
        } catch (DomainException $exception) {
            Log::warning('public.coauthor_confirmation.rejected', [
                'email_hash' => hash('sha256', mb_strtolower(trim((string) $data['email']))),
            ]);

            abort(404, $exception->getMessage());
        }

        Log::info('public.coauthor_confirmation.success', [
            'project_id' => $coauthor->project_id,
            'coauthor_id' => $coauthor->id,
        ]);

        return response('Potwierdzono status współautora.');
    }
}
