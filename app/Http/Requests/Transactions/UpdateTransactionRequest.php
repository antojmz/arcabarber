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
            'services' => ['required', 'array', 'min:1', 'max:20'],
            'services.*' => ['array:service_id,amount'],
            'services.*.service_id' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')],
            'services.*.amount' => ['required', 'integer', 'min:0', 'max:10000000'],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'tip' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:255'],
            'performed_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }

    /**
     * The validated data, typed for the UpdateTransaction action.
     *
     * @return array{barber_id: int, services: list<array{service_id: int, amount: int}>, payment_method_id: int, tip: int, notes: string|null, performed_at: string}
     */
    public function transactionData(): array
    {
        return [
            'barber_id' => $this->integer('barber_id'),
            'services' => array_values(array_map(fn (array $service) => [
                'service_id' => (int) $service['service_id'],
                'amount' => (int) $service['amount'],
            ], $this->validated('services'))),
            'payment_method_id' => $this->integer('payment_method_id'),
            'tip' => $this->integer('tip'),
            'notes' => $this->filled('notes') ? $this->string('notes')->toString() : null,
            'performed_at' => $this->string('performed_at')->toString(),
        ];
    }
}
