<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_edition_id' => ['required', 'exists:budget_editions,id'],
            'project_area_id' => ['required', 'exists:project_areas,id'],
            'title' => ['required', 'string', 'max:600'],
            'localization' => ['required', 'string', 'max:63000'],
            'description' => ['required', 'string', 'max:63000'],
            'goal' => ['required', 'string', 'max:63000'],
            'argumentation' => ['required', 'string', 'max:63000'],
            'availability' => ['required', 'string', 'max:63000'],
            'recipients' => ['required', 'string', 'max:63000'],
            'free_of_charge' => ['required', 'string', 'max:63000'],
            'cost_description' => ['required', 'string', 'max:1000'],
            'cost_amount' => ['required', 'numeric', 'min:0'],
            'support_list' => ['accepted'],
            'support_list_file' => ['required', 'file'],
        ];
    }

    public function attributes(): array
    {
        return [
            'budget_edition_id' => 'edycja',
            'project_area_id' => 'obszar',
            'free_of_charge' => 'bezpłatność',
            'cost_description' => 'pozycja kosztorysu',
            'cost_amount' => 'kwota',
            'support_list' => 'lista poparcia',
            'support_list_file' => 'plik listy poparcia',
        ];
    }
}
