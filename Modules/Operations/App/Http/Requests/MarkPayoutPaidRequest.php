<?php

namespace Modules\Operations\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkPayoutPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['evidence_url' => ['required', 'url', 'max:2048']];
    }
}
