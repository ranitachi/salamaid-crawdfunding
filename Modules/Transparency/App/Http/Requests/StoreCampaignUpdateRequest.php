<?php

namespace Modules\Transparency\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Transparency\App\Enums\CampaignUpdateType;
use Modules\Transparency\Entities\CampaignUpdate;

class StoreCampaignUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CampaignUpdate::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fundReport = CampaignUpdateType::FUND_REPORT->value;

        return [
            'campaign_id' => ['required', 'uuid', Rule::exists('campaigns', 'id')->whereNull('deleted_at')],
            'type' => ['required', Rule::enum(CampaignUpdateType::class)],
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'content' => ['required', 'string', 'min:20', 'max:20000'],
            'payout_id' => ['nullable', 'uuid', "prohibited_unless:type,{$fundReport}", 'exists:payouts,id'],
            'amount_used' => ["required_if:type,{$fundReport}", "prohibited_unless:type,{$fundReport}", 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'campaign_id.exists' => 'Campaign tidak ditemukan.',
            'amount_used.required_if' => 'Nominal penggunaan dana wajib diisi untuk laporan penggunaan dana.',
        ];
    }
}
