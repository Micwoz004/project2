<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePublicProjectRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('map_data') && is_string($this->input('map_data'))) {
            $decoded = json_decode($this->input('map_data'), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([
                    'map_data' => $decoded,
                ]);
            }
        }

        if ($this->has('cost_items') && is_array($this->input('cost_items'))) {
            $costItems = collect($this->input('cost_items'))
                ->filter(fn (array $costItem): bool => collect([
                    $costItem['description'] ?? null,
                    $costItem['amount'] ?? null,
                ])->filter(fn (mixed $value): bool => $value !== null && $value !== '')->isNotEmpty())
                ->values()
                ->all();

            $this->merge([
                'cost_items' => $costItems,
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
            'local' => ['required', 'integer', 'in:1,2'],
            'author_first_name' => ['required', 'string', 'max:127'],
            'author_last_name' => ['required', 'string', 'max:127'],
            'author_email' => ['required', 'email', 'max:255'],
            'author_phone' => ['nullable', 'string', 'max:30'],
            'author_street' => ['nullable', 'string', 'max:127'],
            'author_house_no' => ['nullable', 'string', 'max:20'],
            'author_flat_no' => ['nullable', 'string', 'max:20'],
            'author_post_code' => ['nullable', 'string', 'max:6'],
            'author_city' => ['nullable', 'string', 'max:127'],
            'author_email_agree' => ['nullable', 'boolean'],
            'author_phone_agree' => ['nullable', 'boolean'],
            'author_personal_data_agree' => ['nullable', 'boolean'],
            'author_read_confirm' => ['accepted'],
            'author_contact_details_public' => ['nullable', 'string', 'max:250'],
            'contact_with' => ['required', 'integer', 'in:1,2'],
            'title' => ['required', 'string', 'max:600'],
            'localization' => ['required', 'string', 'max:63000'],
            'address' => ['nullable', 'string', 'max:300'],
            'plot' => ['nullable', 'string', 'max:63000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'map_lng_lat' => ['nullable', 'string', 'max:63000'],
            'map_data' => ['required', 'array'],
            'description' => ['required', 'string', 'max:63000'],
            'goal' => ['required', 'string', 'max:63000'],
            'argumentation' => ['required', 'string', 'max:63000'],
            'availability' => ['required', 'string', 'max:63000'],
            'recipients' => ['required', 'string', 'max:63000'],
            'free_of_charge' => ['required', 'string', 'max:63000'],
            'short_description' => ['nullable', 'string', 'max:700'],
            'additional_cost' => ['nullable', 'string', 'max:500'],
            'cost_items' => ['required_without:cost_description', 'array', 'min:1'],
            'cost_items.*.description' => ['required_with:cost_items', 'string', 'max:1000'],
            'cost_items.*.amount' => ['required_with:cost_items', 'numeric', 'min:0'],
            'cost_description' => ['required_without:cost_items', 'string', 'max:1000'],
            'cost_amount' => ['required_without:cost_items', 'numeric', 'min:0'],
            'consent_to_change' => ['nullable', 'boolean'],
            'show_task_coauthors' => ['nullable', 'boolean'],
            'attachments_anonymized' => ['accepted'],
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
            'coauthors.*.street' => ['nullable', 'string', 'max:128'],
            'coauthors.*.house_no' => ['nullable', 'string', 'max:20'],
            'coauthors.*.flat_no' => ['nullable', 'string', 'max:20'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $phoneAgree = $this->boolean('author_phone_agree');
            $emailAgree = $this->boolean('author_email_agree');

            if (! $phoneAgree && ! $emailAgree) {
                $validator->errors()->add('author_phone_agree', 'Trzeba wybrać co najmniej jedną formę kontaktu autora.');
            }

            if ($phoneAgree && blank($this->input('author_phone'))) {
                $validator->errors()->add('author_phone', 'Przy wyborze kontaktu telefonicznego należy podać telefon autora.');
            }

            if ((int) $this->input('contact_with') === 2 && ! $this->hasFilledCoauthor()) {
                $validator->errors()->add('contact_with', 'Kontakt ze współautorem wymaga dodania co najmniej jednego współautora.');
            }
        });
    }

    /**
     * @return list<array{description: string, amount: float}>
     */
    public function costItems(): array
    {
        $validated = $this->validated();

        if (array_key_exists('cost_items', $validated)) {
            return collect($validated['cost_items'])
                ->map(fn (array $costItem): array => [
                    'description' => trim($costItem['description']),
                    'amount' => (float) $costItem['amount'],
                ])
                ->values()
                ->all();
        }

        return [[
            'description' => trim($validated['cost_description']),
            'amount' => (float) $validated['cost_amount'],
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    public function authorSnapshot(): array
    {
        $validated = $this->validated();

        return [
            'first_name' => $validated['author_first_name'],
            'last_name' => $validated['author_last_name'],
            'email' => $validated['author_email'],
            'phone' => $validated['author_phone'] ?? null,
            'street' => $validated['author_street'] ?? null,
            'house_no' => $validated['author_house_no'] ?? null,
            'flat_no' => $validated['author_flat_no'] ?? null,
            'post_code' => $validated['author_post_code'] ?? null,
            'city' => $validated['author_city'] ?? null,
            'email_agree' => (bool) ($validated['author_email_agree'] ?? false),
            'phone_agree' => (bool) ($validated['author_phone_agree'] ?? false),
            'personal_data_agree' => (bool) ($validated['author_personal_data_agree'] ?? false),
            'read_confirm' => true,
            'contact_details_public' => $validated['author_contact_details_public'] ?? null,
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
                'street' => $coauthor['street'] ?? null,
                'house_no' => $coauthor['house_no'] ?? null,
                'flat_no' => $coauthor['flat_no'] ?? null,
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

    private function hasFilledCoauthor(): bool
    {
        return collect($this->input('coauthors', []))
            ->contains(fn (array $coauthor): bool => collect([
                $coauthor['first_name'] ?? null,
                $coauthor['last_name'] ?? null,
                $coauthor['email'] ?? null,
                $coauthor['phone'] ?? null,
            ])->filter()->isNotEmpty());
    }

    public function attributes(): array
    {
        return [
            'budget_edition_id' => 'edycja',
            'project_area_id' => 'obszar',
            'category_id' => 'kategoria',
            'local' => 'typ projektu',
            'author_first_name' => 'imię autora',
            'author_last_name' => 'nazwisko autora',
            'author_email' => 'e-mail autora',
            'author_phone' => 'telefon autora',
            'author_read_confirm' => 'potwierdzenie regulaminu',
            'author_email_agree' => 'zgoda na kontakt e-mail autora',
            'author_phone_agree' => 'zgoda na kontakt telefoniczny autora',
            'contact_with' => 'preferowany kontakt',
            'address' => 'adres',
            'plot' => 'działka',
            'lat' => 'szerokość geograficzna',
            'lng' => 'długość geograficzna',
            'map_lng_lat' => 'współrzędne mapy',
            'map_data' => 'dane mapy',
            'free_of_charge' => 'bezpłatność',
            'short_description' => 'skrócony opis',
            'additional_cost' => 'koszty utrzymania',
            'cost_items' => 'kosztorys',
            'cost_description' => 'pozycja kosztorysu',
            'cost_amount' => 'kwota',
            'attachments_anonymized' => 'oświadczenie o załącznikach',
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
