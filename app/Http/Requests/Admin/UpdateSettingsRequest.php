<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAdminPermission('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:80'],
            'app_tagline' => ['nullable', 'string', 'max:120'],
            'public_tagline' => ['nullable', 'string', 'max:120'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'primary_soft_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_icon' => ['required', 'string', 'max:10'],
            // URL fields remain accepted for backward compatibility with existing settings/API clients.
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'favicon_url' => ['nullable', 'url', 'max:2048'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico,svg', 'max:512'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_address' => ['nullable', 'string', 'max:190'],
            'contact_city' => ['nullable', 'string', 'max:80'],
            'contact_country' => ['nullable', 'string', 'max:80'],
        ];
    }
}
