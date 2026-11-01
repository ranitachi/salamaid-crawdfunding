<?php

namespace Modules\Transparency\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Transparency\Entities\CampaignUpdate;

class StoreCampaignUpdateAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $update = $this->route('update');

        return $update instanceof CampaignUpdate
            && ($this->user()?->can('manageAttachments', $update) ?? false);
    }

    /**
     * `mimes` checks the detected content type, not only the client extension.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('transparency.evidence.mimes')),
                'max:'.config('transparency.evidence.max_kilobytes'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Bukti harus berupa '.implode(', ', config('transparency.evidence.mimes')).'.',
            'file.max' => 'Ukuran bukti maksimal '.(config('transparency.evidence.max_kilobytes') / 1024).' MB.',
        ];
    }
}
