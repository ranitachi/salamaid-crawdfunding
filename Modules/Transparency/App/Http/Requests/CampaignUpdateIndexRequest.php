<?php

namespace Modules\Transparency\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Transparency\App\Enums\CampaignUpdateStatus;
use Modules\Transparency\App\Enums\CampaignUpdateType;
use Modules\Transparency\Entities\CampaignUpdate;

class CampaignUpdateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CampaignUpdate::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(CampaignUpdateStatus::class)],
            'type' => ['nullable', Rule::enum(CampaignUpdateType::class)],
            'campaign_id' => ['nullable', 'uuid'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
