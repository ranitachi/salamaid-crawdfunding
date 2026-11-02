<?php

namespace Modules\Operations\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;
use Modules\Operations\Entities\Payout;

/**
 * Exactly one evidence source:
 * - `evidence_file`: new upload (type checked from file content, size-limited);
 * - `evidence_url`:  legacy http(s) URL;
 * - `evidence`:      legacy string field — an http(s) URL, or a private path that
 *                    already exists inside this payout's own evidence directory.
 */
class MarkPayoutPaidRequest extends FormRequest
{
    private const SOURCES = ['evidence_file', 'evidence_url', 'evidence'];

    public function authorize(): bool
    {
        $payout = $this->route('payout');

        return $payout instanceof Payout && ($this->user()?->can('markPaid', $payout) ?? false);
    }

    public function rules(): array
    {
        $config = (array) config('operations.evidence');

        return [
            // `mimes` guesses the extension from the file content and `mimetypes`
            // checks the server-detected MIME; the client-sent type is not trusted.
            'evidence_file' => [
                'nullable', 'file',
                'mimes:'.implode(',', $config['mimes']),
                'mimetypes:'.implode(',', $config['mimetypes']),
                'max:'.$config['max_kilobytes'],
            ],
            'evidence_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'evidence' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $provided = array_values(array_filter(self::SOURCES, fn ($field) => $this->hasEvidenceInput($field)));

            if ($provided === []) {
                $validator->errors()->add('evidence', 'Bukti transfer wajib diisi: unggah file atau isi URL bukti.');

                return;
            }

            if (count($provided) > 1) {
                $validator->errors()->add('evidence', 'Isi satu jenis bukti saja: file, URL, atau path bukti.');

                return;
            }

            if ($provided[0] === 'evidence' && $this->legacyEvidence() === null) {
                $validator->errors()->add('evidence', 'Format bukti tidak valid. Gunakan URL http(s) atau unggah file.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'evidence_file.mimes' => 'Bukti harus berupa JPG, PNG, atau PDF.',
            'evidence_file.mimetypes' => 'Isi file bukti bukan JPG, PNG, atau PDF.',
            'evidence_file.max' => 'Ukuran bukti maksimal '.(config('operations.evidence.max_kilobytes') / 1024).' MB.',
            'evidence_url.url' => 'URL bukti harus diawali http:// atau https://.',
        ];
    }

    /**
     * @return array{type: 'file', file: UploadedFile}|array{type: 'url', url: string}|array{type: 'path', path: string}
     */
    public function validatedEvidence(): array
    {
        if ($this->hasEvidenceInput('evidence_file')) {
            return ['type' => 'file', 'file' => $this->file('evidence_file')];
        }

        if ($this->hasEvidenceInput('evidence_url')) {
            return ['type' => 'url', 'url' => trim((string) $this->validated('evidence_url'))];
        }

        return $this->legacyEvidence() ?? throw new \LogicException('validatedEvidence() called before validation.');
    }

    /** @return array{type: 'url', url: string}|array{type: 'path', path: string}|null */
    private function legacyEvidence(): ?array
    {
        $value = trim((string) $this->input('evidence'));

        if ($this->isHttpUrl($value)) {
            return ['type' => 'url', 'url' => $value];
        }

        $payout = $this->route('payout');
        $extensions = implode('|', array_map('preg_quote', (array) config('operations.evidence.mimes')));
        $directory = preg_quote($payout instanceof Payout ? $payout->evidenceDirectory() : '', '#');

        // Only "<evidence dir>/<payout id>/<generated name>.<ext>": no "..", no other payout, no arbitrary path.
        if ($payout instanceof Payout && preg_match("#^{$directory}/[A-Za-z0-9]{16,64}\.({$extensions})$#i", $value)) {
            return ['type' => 'path', 'path' => $value];
        }

        return null;
    }

    private function hasEvidenceInput(string $field): bool
    {
        return $field === 'evidence_file' ? $this->hasFile($field) : $this->filled($field);
    }

    private function isHttpUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
