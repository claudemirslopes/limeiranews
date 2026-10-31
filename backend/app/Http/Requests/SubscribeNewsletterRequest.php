<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:120'],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'É necessário aceitar receber a newsletter para se inscrever.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'e-mail',
            'name' => 'nome',
        ];
    }
}
