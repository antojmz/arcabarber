<?php

namespace App\Http\Requests\Admin;

use App\Models\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PaymentMethod|null $paymentMethod */
        $paymentMethod = $this->route('payment_method');

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique(PaymentMethod::class)->ignore($paymentMethod?->id)],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique(PaymentMethod::class)->ignore($paymentMethod?->id)],
            'atm_fee_rate' => ['required', 'numeric', 'between:0,100'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
