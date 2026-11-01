<?php

namespace Modules\Transparency\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Transparency\Entities\CampaignUpdate;

/** Reject (request revision) and unpublish both require a written reason. */
class CampaignUpdateReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $update = $this->route('update');
        $ability = $this->routeIs('admin.transparency.unpublish') ? 'publish' : 'review';

        return $update instanceof CampaignUpdate
            && ($this->user()?->can($ability, $update) ?? false);
    }

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
            'reason.required' => 'Alasan wajib diisi.',
            'reason.min' => 'Alasan minimal 5 karakter.',
        ];
    }
}
