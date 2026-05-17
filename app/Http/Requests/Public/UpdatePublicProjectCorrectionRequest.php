<?php

namespace App\Http\Requests\Public;

use App\Domain\Projects\Enums\ProjectCorrectionField;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCorrection;
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
        $rules = [];
        $allowedFields = $this->allowedFields();

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::ProjectArea)) {
            $rules['project_area_id'] = ['sometimes', 'required', 'exists:project_areas,id'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Category)) {
            $rules['category_id'] = ['sometimes', 'required', 'exists:categories,id'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Title)) {
            $rules['title'] = ['sometimes', 'required', 'string', 'max:600'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Localization)) {
            $rules['localization'] = ['sometimes', 'required', 'string', 'max:63000'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::MapData)) {
            $rules['map_data'] = ['sometimes', 'nullable', 'array'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Description)) {
            $rules['description'] = ['sometimes', 'required', 'string', 'max:63000'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Goal)) {
            $rules['goal'] = ['sometimes', 'required', 'string', 'max:63000'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Argumentation)) {
            $rules['argumentation'] = ['sometimes', 'required', 'string', 'max:63000'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Availability)) {
            $rules['availability'] = ['sometimes', 'required', 'string', 'max:63000'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Recipients)) {
            $rules['recipients'] = ['sometimes', 'required', 'string', 'max:63000'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::FreeOfCharge)) {
            $rules['free_of_charge'] = ['sometimes', 'required', 'string', 'max:63000'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Cost)) {
            $rules['cost_items'] = ['sometimes', 'array'];
            $rules['cost_items.*.description'] = ['required_with:cost_items', 'string', 'max:1000'];
            $rules['cost_items.*.amount'] = ['required_with:cost_items', 'numeric', 'min:0'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::SupportAttachment)) {
            $rules['support_list_files'] = ['sometimes', 'array', 'max:5'];
            $rules['support_list_files.*'] = ['file'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::AgreementAttachment)) {
            $rules['owner_agreement_files'] = ['sometimes', 'array', 'max:5'];
            $rules['owner_agreement_files.*'] = ['file'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::MapAttachment)) {
            $rules['map_files'] = ['sometimes', 'array', 'max:5'];
            $rules['map_files.*'] = ['file'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::ParentAgreementAttachment)) {
            $rules['parent_agreement_files'] = ['sometimes', 'array', 'max:5'];
            $rules['parent_agreement_files.*'] = ['file'];
        }

        if ($this->isFieldAllowed($allowedFields, ProjectCorrectionField::Attachments)) {
            $rules['attachment_files'] = ['sometimes', 'array', 'max:10'];
            $rules['attachment_files.*'] = ['file'];
        }

        return $rules;
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

    /**
     * @param  list<string>  $allowedFields
     */
    private function isFieldAllowed(array $allowedFields, ProjectCorrectionField $field): bool
    {
        return in_array($field->value, $allowedFields, true);
    }

    /**
     * @return list<string>
     */
    private function allowedFields(): array
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return [];
        }

        $correction = $project->corrections()
            ->where('correction_done', false)
            ->where('correction_deadline', '>', now())
            ->latest()
            ->first();

        return $correction instanceof ProjectCorrection ? $correction->allowed_fields : [];
    }
}
