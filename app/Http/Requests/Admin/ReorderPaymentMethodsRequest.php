<?php

namespace App\Http\Requests\Admin;

use App\Models\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderPaymentMethodsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', Rule::exists(PaymentMethod::class, 'id')],
        ];
    }
}
