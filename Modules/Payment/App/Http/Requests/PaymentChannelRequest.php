<?php

namespace Modules\Payment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentChannelRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasAdminPermission('payments.manage') ?? false; }

    public function rules(): array
    {
        $channel = $this->route('channel');
        return [
            'provider' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:payment_channels,code,'.($channel?->id ?? 'NULL')],
            'method' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
