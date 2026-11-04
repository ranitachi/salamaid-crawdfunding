<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicPartnerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_reference' => ['required', 'string', 'regex:/^SM-[A-Z0-9]{10}$/'],
            'email' => ['required', 'email:rfc,filter'],
        ];
    }

    public function attributes(): array
    {
        return ['application_reference' => 'nomor pengajuan', 'email' => 'email'];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Email harus berupa alamat email yang valid.',
            'regex' => 'Format nomor pengajuan tidak valid.',
        ];
    }
}
