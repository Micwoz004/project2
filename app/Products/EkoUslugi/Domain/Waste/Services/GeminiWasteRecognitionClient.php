<?php

namespace App\Products\EkoUslugi\Domain\Waste\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiWasteRecognitionClient implements WasteRecognitionClient
{
    public function recognize(array $files): array
    {
        $apiKey = config('eko_uslugi.gemini.api_key');
        $model = config('eko_uslugi.gemini.model');

        Log::info('eko_uslugi.waste.recognition.start', [
            'files_count' => count($files),
            'model' => $model,
        ]);

        if (! is_string($apiKey) || $apiKey === '') {
            Log::warning('eko_uslugi.waste.recognition.rejected_missing_api_key');

            throw new RuntimeException('Brak konfiguracji klucza Gemini dla rozpoznawania odpadów.');
        }

        try {
            $results = [];

            foreach ($files as $file) {
                $results[] = $this->recognizeSingleFile($file, $apiKey, (string) $model);
            }

            Log::info('eko_uslugi.waste.recognition.success', [
                'files_count' => count($results),
            ]);

            return $results;
        } catch (ConnectionException $exception) {
            Log::error('eko_uslugi.waste.recognition.failed', [
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Nie udało się połączyć z usługą rozpoznawania odpadów.', previous: $exception);
        }
    }

    /**
     * @return array{fileName: string, objectSummary: string|null, synonyms: list<string>}
     */
    private function recognizeSingleFile(UploadedFile $file, string $apiKey, string $model): array
    {
        $prompt = 'Rozpoznaj przedmiot na zdjęciu jako odpad komunalny. Zwróć krótki polski opis i 3-6 nazw/synonimów odpadu, bez danych osobowych.';
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                            'data' => base64_encode($file->get()),
                        ],
                    ],
                ],
            ]],
        ];

        $response = Http::timeout((int) config('eko_uslugi.gemini.timeout', 20))
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", $payload)
            ->throw()
            ->json();

        $text = data_get($response, 'candidates.0.content.parts.0.text');
        $summary = is_string($text) ? trim($text) : null;

        return [
            'fileName' => $file->getClientOriginalName(),
            'objectSummary' => $summary,
            'synonyms' => $summary === null ? [] : collect(preg_split('/[,;\n]+/', $summary) ?: [])
                ->map(fn (string $item): string => trim($item))
                ->filter()
                ->take(6)
                ->values()
                ->all(),
        ];
    }
}
