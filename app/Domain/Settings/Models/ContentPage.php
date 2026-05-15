<?php

namespace App\Domain\Settings\Models;

use App\Domain\BudgetEditions\Models\BudgetEdition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPage extends Model
{
    public const SYMBOL_VOID = 'V';

    public const SYMBOL_STATEMENT = 'S';

    public const SYMBOL_ABSENCE = 'A';

    public const SYMBOL_INFORMATION = 'I';

    public const SYMBOL_THANKYOU = 'TY';

    public const SYMBOL_WELCOME = 'W';

    public const SYMBOL_TOKEN = 'T';

    public const LEGACY_SYMBOLS = [
        self::SYMBOL_VOID,
        self::SYMBOL_STATEMENT,
        self::SYMBOL_ABSENCE,
        self::SYMBOL_INFORMATION,
        self::SYMBOL_THANKYOU,
        self::SYMBOL_WELCOME,
        self::SYMBOL_TOKEN,
    ];

    protected $guarded = [];

    public function budgetEdition(): BelongsTo
    {
        return $this->belongsTo(BudgetEdition::class);
    }
}
