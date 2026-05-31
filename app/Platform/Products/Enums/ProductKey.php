<?php

namespace App\Platform\Products\Enums;

enum ProductKey: string
{
    case CivicBudget = 'civic_budget';
    case Consultations = 'consultations';
    case EcoServices = 'eco_services';

    public function label(): string
    {
        return match ($this) {
            self::CivicBudget => 'Budżet obywatelski',
            self::Consultations => 'Konsultacje społeczne',
            self::EcoServices => 'Ekousługi',
        };
    }

    public function adminPanelId(): string
    {
        return match ($this) {
            self::CivicBudget => 'civic-budget',
            self::Consultations => 'consultations',
            self::EcoServices => 'eco-services',
        };
    }
}
