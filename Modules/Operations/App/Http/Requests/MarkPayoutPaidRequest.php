<?php

namespace Modules\Operations\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class MarkPayoutPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evidence_url' => ['nullable', 'string', 'max:2048'],
            'evidence' => ['required', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.required' => 'Bukti transfer wajib diisi.',
            'evidence.string' => 'Format bukti tidak valid.',
            'evidence.max' => 'Bukti terlalu besar.',
            'evidence_url.max' => 'Bukti terlalu besar.',
        ];
    }

    /**
     * Get the validated evidence data, preferring 'evidence' over 'evidence_url'.
     *
     * Returns ['type' => 'url'|'path', 'value' => string]
     */
    public function validatedEvidence(): array
    {
        // Prefer 'evidence' field if present and valid
        if ($this->filled('evidence')) {
            $evidence = $this->string('evidence');

            // Check if it's a private storage path
            if (Str::startsWith($evidence, 'private/') || preg_match('/^[0-9a-f]{32,36}$/i', $evidence)) {
                return [
                    'type' => 'path',
                    'value' => $evidence,
                ];
            }

            // Otherwise treat as URL (backward compatibility)
            if (filter_var($evidence, FILTER_VALIDATE_URL)) {
                return [
                    'type' => 'url',
                    'value' => $evidence,
                ];
            }

            // If it looks like a filename, treat as path
            return [
                'type' => 'path',
                'value' => $evidence,
            ];
        }

        // Fall back to 'evidence_url' field
        if ($this->filled('evidence_url')) {
            $evidenceUrl = $this->string('evidence_url');

            if (filter_var($evidenceUrl, FILTER_VALIDATE_URL)) {
                return [
                    'type' => 'url',
                    'value' => $evidenceUrl,
                ];
            }

            // Treat as path
            return [
                'type' => 'path',
                'value' => $evidenceUrl,
            ];
        }

        // If neither is filled, return empty (will be validated as required)
        return [
            'type' => 'url',
            'value' => '',
        ];
    }
}