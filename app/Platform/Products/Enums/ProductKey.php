<?php

namespace App\Platform\Products\Enums;

enum ProductKey: string
{
    case CivicBudget = 'civic_budget';
    case Consultations = 'consultations';
    case EkoUslugi = 'eko_uslugi';

    public function label(): string
    {
        return match ($this) {
            self::CivicBudget => 'Budżet obywatelski',
            self::Consultations => 'Konsultacje społeczne',
            self::EkoUslugi => 'Eko usługi',
        };
    }

    public function adminPanelId(): string
    {
        return match ($this) {
            self::CivicBudget => 'civic-budget',
            self::Consultations => 'consultations',
            self::EkoUslugi => 'eko-uslugi',
        };
    }
}
