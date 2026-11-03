<?php

namespace Modules\Transparency\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Entities\Campaign;
use Modules\Transparency\App\Enums\CampaignUpdateType;
use Modules\Transparency\Entities\CampaignUpdate;

class StoreCampaignUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->can('create', CampaignUpdate::class)) {
            return false;
        }

        if ($user->isOrganizationAdmin()) {
            $campaign = Campaign::query()->find($this->input('campaign_id'));

            return $campaign && in_array((string) $campaign->organization_id, $user->managedOrganizationIds(), true);
        }

        return true;
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
            'amount_used.prohibited_unless' => 'Nominal digunakan hanya boleh diisi untuk jenis laporan penggunaan dana.',
            'payout_id.prohibited_unless' => 'Payout hanya boleh dipilih untuk jenis laporan penggunaan dana.',
        ];
    }
}
