<?php

namespace App\Products\EcoServices\Domain\Waste\Services;

use Illuminate\Http\UploadedFile;

interface WasteRecognitionClient
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{fileName: string, objectSummary: string|null, synonyms: list<string>}>
     */
    public function recognize(array $files): array;
}
