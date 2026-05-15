<?php

namespace App\Http\Requests\Public;

use App\Domain\Voting\Enums\CitizenConfirmation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CastPublicVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_edition_id' => ['required', 'exists:budget_editions,id'],
            'pesel' => ['required', 'string', 'size:11'],
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'mother_last_name' => ['required', 'string', 'max:127'],
            'phone' => ['required', 'string', 'max:30'],
            'sms_token' => ['required', 'string', 'size:6'],
            'local_project_id' => ['nullable', 'exists:projects,id'],
            'citywide_project_id' => ['nullable', 'exists:projects,id'],
            'citizen_confirm' => ['nullable', Rule::enum(CitizenConfirmation::class)],
            'confirm_missing_category' => ['sometimes', 'accepted'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_confirm' => ['sometimes', 'accepted'],
        ];
    }
}
