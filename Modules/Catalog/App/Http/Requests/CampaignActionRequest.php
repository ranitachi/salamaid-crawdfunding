<?php

namespace Modules\Catalog\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalog\Entities\Campaign;

abstract class CampaignActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof Campaign
            && ($this->user()?->can('moderate', $campaign) ?? false);
    }
}
