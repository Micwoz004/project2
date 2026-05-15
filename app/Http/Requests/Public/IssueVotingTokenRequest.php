<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class IssueVotingTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pesel' => ['required', 'string', 'size:11'],
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'mother_last_name' => ['required', 'string', 'max:127'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }
}
