<?php

namespace Modules\Catalog\App\Http\Requests;

class SuspendCampaignRequest extends CampaignActionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan penangguhan campaign wajib diisi.',
            'reason.min' => 'Alasan penangguhan minimal 5 karakter.',
        ];
    }
}
