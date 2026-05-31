<?php

namespace App\Platform\Products\Enums;

enum ProductKey: string
{
    case CivicBudget = 'civic_budget';
    case Consultations = 'consultations';
    case EcoUslugi = 'eco_uslugi';

    public function label(): string
    {
        return match ($this) {
            self::CivicBudget => 'Budżet obywatelski',
            self::Consultations => 'Konsultacje społeczne',
            self::EcoUslugi => 'Ekousługi',
        };
    }

    public function adminPanelId(): string
    {
        return match ($this) {
            self::CivicBudget => 'civic-budget',
            self::Consultations => 'consultations',
            self::EcoUslugi => 'eco-uslugi',
        };
    }
}
