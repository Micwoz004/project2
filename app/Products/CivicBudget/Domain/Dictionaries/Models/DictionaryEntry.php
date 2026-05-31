<?php

namespace App\Products\CivicBudget\Domain\Dictionaries\Models;

use App\Platform\Clients\Concerns\BelongsToClient;

use App\Products\CivicBudget\Domain\Dictionaries\Enums\DictionaryKind;
use Illuminate\Database\Eloquent\Model;

class DictionaryEntry extends Model
{
    use BelongsToClient;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kind' => DictionaryKind::class,
            'active' => 'boolean',
        ];
    }
}
