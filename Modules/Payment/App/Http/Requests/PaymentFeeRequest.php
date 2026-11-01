<?php

namespace Modules\Payment\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentFeeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasAdminPermission('payments.manage') ?? false; }

    public function rules(): array
    {
        return [
            'fee_flat' => ['required', 'integer', 'min:0'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_fee' => ['required', 'integer', 'min:0'],
            'max_fee' => ['nullable', 'integer', 'gte:min_fee'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
