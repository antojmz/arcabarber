<?php

namespace App\Http\Requests\Transactions;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'barber_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Barber->value)],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'amount' => ['required', 'integer', 'min:0', 'max:10000000'],
            'tip' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:255'],
            'performed_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * The validated data, typed for the UpdateTransaction action.
     *
     * @return array{barber_id: int, service_id: int, payment_method_id: int, amount: int, tip: int, notes: string|null, performed_at: string}
     */
    public function transactionData(): array
    {
        return [
            'barber_id' => $this->integer('barber_id'),
            'service_id' => $this->integer('service_id'),
            'payment_method_id' => $this->integer('payment_method_id'),
            'amount' => $this->integer('amount'),
            'tip' => $this->integer('tip'),
            'notes' => $this->filled('notes') ? $this->string('notes')->toString() : null,
            'performed_at' => $this->string('performed_at')->toString(),
        ];
    }
}
