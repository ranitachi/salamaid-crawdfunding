<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicDonationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc,filter', 'max:190'],
            'mobile' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{8,24}$/'],
            'amount' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'anonymous' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:280'],
        ];
    }
}
