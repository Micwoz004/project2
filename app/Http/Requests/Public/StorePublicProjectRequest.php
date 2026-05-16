<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicProjectRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('map_data') || ! is_string($this->input('map_data'))) {
            return;
        }

        $decoded = json_decode($this->input('map_data'), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $this->merge([
                'map_data' => $decoded,
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_edition_id' => ['required', 'exists:budget_editions,id'],
            'project_area_id' => ['required', 'exists:project_areas,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:600'],
            'localization' => ['required', 'string', 'max:63000'],
            'address' => ['nullable', 'string', 'max:300'],
            'plot' => ['nullable', 'string', 'max:63000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'map_lng_lat' => ['nullable', 'string', 'max:63000'],
            'map_data' => ['nullable', 'array'],
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
            'owner_agreement_files' => ['sometimes', 'array', 'max:5'],
            'owner_agreement_files.*' => ['file'],
            'map_files' => ['sometimes', 'array', 'max:5'],
            'map_files.*' => ['file'],
            'parent_agreement_files' => ['sometimes', 'array', 'max:5'],
            'parent_agreement_files.*' => ['file'],
            'attachment_files' => ['sometimes', 'array', 'max:10'],
            'attachment_files.*' => ['file'],
            'coauthors' => ['sometimes', 'array', 'max:2'],
            'coauthors.*.first_name' => ['nullable', 'string', 'max:127'],
            'coauthors.*.last_name' => ['nullable', 'string', 'max:127'],
            'coauthors.*.email' => ['nullable', 'email', 'max:127'],
            'coauthors.*.phone' => ['nullable', 'string', 'max:30'],
            'coauthors.*.post_code' => ['nullable', 'string', 'max:6'],
            'coauthors.*.city' => ['nullable', 'string', 'max:127'],
            'coauthors.*.personal_data_agree' => ['nullable', 'boolean'],
            'coauthors.*.name_agree' => ['nullable', 'boolean'],
            'coauthors.*.data_evaluation_agree' => ['nullable', 'boolean'],
            'coauthors.*.read_confirm' => ['nullable', 'boolean'],
            'coauthors.*.confirm' => ['nullable', 'boolean'],
            'coauthors.*.email_agree' => ['nullable', 'boolean'],
            'coauthors.*.phone_agree' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function coauthors(): array
    {
        return collect($this->validated('coauthors', []))
            ->filter(fn (array $coauthor): bool => collect([
                $coauthor['first_name'] ?? null,
                $coauthor['last_name'] ?? null,
                $coauthor['email'] ?? null,
                $coauthor['phone'] ?? null,
            ])->filter()->isNotEmpty())
            ->map(fn (array $coauthor): array => [
                'first_name' => $coauthor['first_name'] ?? '',
                'last_name' => $coauthor['last_name'] ?? '',
                'email' => $coauthor['email'] ?? null,
                'phone' => $coauthor['phone'] ?? null,
                'post_code' => $coauthor['post_code'] ?? null,
                'city' => $coauthor['city'] ?? null,
                'personal_data_agree' => (bool) ($coauthor['personal_data_agree'] ?? false),
                'name_agree' => (bool) ($coauthor['name_agree'] ?? false),
                'data_evaluation_agree' => (bool) ($coauthor['data_evaluation_agree'] ?? false),
                'read_confirm' => (bool) ($coauthor['read_confirm'] ?? false),
                'confirm' => (bool) ($coauthor['confirm'] ?? false),
                'email_agree' => (bool) ($coauthor['email_agree'] ?? false),
                'phone_agree' => (bool) ($coauthor['phone_agree'] ?? false),
            ])
            ->values()
            ->all();
    }

    public function attributes(): array
    {
        return [
            'budget_edition_id' => 'edycja',
            'project_area_id' => 'obszar',
            'category_id' => 'kategoria',
            'address' => 'adres',
            'plot' => 'działka',
            'lat' => 'szerokość geograficzna',
            'lng' => 'długość geograficzna',
            'map_lng_lat' => 'współrzędne mapy',
            'map_data' => 'dane mapy',
            'free_of_charge' => 'bezpłatność',
            'cost_description' => 'pozycja kosztorysu',
            'cost_amount' => 'kwota',
            'support_list' => 'lista poparcia',
            'support_list_file' => 'plik listy poparcia',
            'owner_agreement_files' => 'zgody właściciela',
            'map_files' => 'załączniki mapy',
            'parent_agreement_files' => 'zgody rodzica lub opiekuna',
            'attachment_files' => 'pozostałe załączniki',
            'coauthors' => 'współautorzy',
        ];
    }
}
