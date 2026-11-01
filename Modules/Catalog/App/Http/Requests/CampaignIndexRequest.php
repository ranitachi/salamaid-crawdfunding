<?php

namespace Modules\Catalog\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalog\App\Enums\CampaignStatus;
use Modules\Catalog\Entities\Campaign;

class CampaignIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Campaign::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:'.implode(',', array_keys(CampaignStatus::options()))],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'featured' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
