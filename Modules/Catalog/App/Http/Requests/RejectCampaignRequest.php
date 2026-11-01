<?php

namespace Modules\Catalog\App\Http\Requests;

class RejectCampaignRequest extends CampaignActionRequest
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
            'reason.required' => 'Alasan penolakan campaign wajib diisi.',
            'reason.min' => 'Alasan penolakan minimal 5 karakter.',
        ];
    }
}
