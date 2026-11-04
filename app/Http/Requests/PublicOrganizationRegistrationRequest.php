<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Entities\Organization;

class PublicOrganizationRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reference = (string) $this->input('application_reference', '');
        if ($reference === '') return true;

        return $this->session()->get('partner_status.reference') === strtoupper($reference)
            && Organization::query()->where('application_reference', $reference)->where('status', 'rejected')->exists();
    }

    public function rules(): array
    {
        $ignoreUserId = null;
        if ($this->filled('application_reference')) {
            $organization = Organization::query()
                ->where('application_reference', $this->input('application_reference'))
                ->first();
            $ignoreUserId = $organization?->members()->where('role', 'owner')->value('user_id');
        }

        return [
            'application_reference' => ['nullable', 'string', 'regex:/^SM-[A-Z0-9]{10}$/'],
            'name' => ['required', 'string', 'max:160'],
            'organization_type' => ['required', 'in:yayasan,lembaga_sosial,komunitas,organisasi'],
            'responsible_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc,filter', 'max:190', Rule::unique('users', 'email')->ignore($ignoreUserId)],
            'mobile' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{8,24}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:10000'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'instagram_url' => ['nullable', 'url', 'max:2048'],
            'facebook_url' => ['nullable', 'url', 'max:2048'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'verification_document' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lembaga',
            'organization_type' => 'jenis lembaga',
            'responsible_name' => 'nama penanggung jawab',
            'email' => 'email akun',
            'mobile' => 'nomor WhatsApp',
            'password' => 'password',
            'address' => 'alamat lengkap',
            'city' => 'kota/kabupaten',
            'province' => 'provinsi',
            'description' => 'deskripsi lembaga',
            'website_url' => 'website',
            'instagram_url' => 'Instagram',
            'facebook_url' => 'Facebook',
            'logo' => 'logo lembaga',
            'verification_document' => 'dokumen pendukung',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'unique' => ':attribute sudah terdaftar. Gunakan email lain.',
            'in' => 'Pilihan :attribute tidak valid.',
            'confirmed' => 'Konfirmasi :attribute tidak sama.',
            'min' => ':attribute minimal harus :min karakter.',
            'url' => ':attribute harus berupa URL yang valid.',
            'regex' => 'Format :attribute tidak valid.',
            'mimes' => 'Format :attribute harus berupa: :values.',
            'max' => 'Ukuran atau panjang :attribute melebihi batas yang diizinkan.',
        ];
    }
}
