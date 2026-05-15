<?php

namespace App\Domain\LegacyImport\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyImportBatch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
