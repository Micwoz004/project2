<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateResidentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->user()->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'street' => ['nullable', 'string', 'max:127'],
            'house_no' => ['nullable', 'string', 'max:20'],
            'flat_no' => ['nullable', 'string', 'max:20'],
            'post_code' => ['nullable', 'string', 'max:6'],
            'city' => ['nullable', 'string', 'max:127'],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'imię',
            'last_name' => 'nazwisko',
            'email' => 'adres e-mail',
            'phone' => 'telefon',
            'street' => 'ulica',
            'house_no' => 'numer domu',
            'flat_no' => 'numer lokalu',
            'post_code' => 'kod pocztowy',
            'city' => 'miejscowość',
            'current_password' => 'obecne hasło',
            'password' => 'nowe hasło',
        ];
    }
}
