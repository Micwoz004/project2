<?php

namespace App\Domain\Dictionaries\Models;

use App\Domain\Dictionaries\Enums\DictionaryKind;
use Illuminate\Database\Eloquent\Model;

class DictionaryEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kind' => DictionaryKind::class,
            'active' => 'boolean',
        ];
    }
}
