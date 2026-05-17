<?php

namespace App\Http\Requests\Public;

use App\Domain\Projects\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicProjectCorrectionRequest extends FormRequest
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
        $project = $this->route('project');
        $user = $this->user();

        return $project instanceof Project
            && $user instanceof User
            && $user->can('update', $project);
    }

    public function rules(): array
    {
        return [
            'project_area_id' => ['sometimes', 'required', 'exists:project_areas,id'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:600'],
            'localization' => ['sometimes', 'required', 'string', 'max:63000'],
            'map_data' => ['sometimes', 'nullable', 'array'],
            'description' => ['sometimes', 'required', 'string', 'max:63000'],
            'goal' => ['sometimes', 'required', 'string', 'max:63000'],
            'argumentation' => ['sometimes', 'required', 'string', 'max:63000'],
            'availability' => ['sometimes', 'required', 'string', 'max:63000'],
            'recipients' => ['sometimes', 'required', 'string', 'max:63000'],
            'free_of_charge' => ['sometimes', 'required', 'string', 'max:63000'],
            'cost_items' => ['sometimes', 'array'],
            'cost_items.*.description' => ['required_with:cost_items', 'string', 'max:1000'],
            'cost_items.*.amount' => ['required_with:cost_items', 'numeric', 'min:0'],
            'support_list_files' => ['sometimes', 'array', 'max:5'],
            'support_list_files.*' => ['file'],
            'owner_agreement_files' => ['sometimes', 'array', 'max:5'],
            'owner_agreement_files.*' => ['file'],
            'map_files' => ['sometimes', 'array', 'max:5'],
            'map_files.*' => ['file'],
            'parent_agreement_files' => ['sometimes', 'array', 'max:5'],
            'parent_agreement_files.*' => ['file'],
            'attachment_files' => ['sometimes', 'array', 'max:10'],
            'attachment_files.*' => ['file'],
        ];
    }

    public function actor(): User
    {
        return $this->user();
    }

    public function attributes(): array
    {
        return [
            'project_area_id' => 'obszar',
            'category_id' => 'kategoria',
            'free_of_charge' => 'bezpłatność',
            'map_data' => 'dane mapy',
            'cost_items' => 'kosztorys',
            'cost_items.*.description' => 'opis pozycji kosztorysu',
            'cost_items.*.amount' => 'kwota pozycji kosztorysu',
            'support_list_files' => 'listy poparcia',
            'owner_agreement_files' => 'zgody właściciela',
            'map_files' => 'załączniki mapy',
            'parent_agreement_files' => 'zgody rodzica lub opiekuna',
            'attachment_files' => 'pozostałe załączniki',
        ];
    }
}
