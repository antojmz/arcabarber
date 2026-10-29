<?php

namespace App\Http\Requests\Transactions;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     * Barber, amounts and date are only accepted from admins.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'services' => ['required', 'array', 'min:1', 'max:20'],
            'services.*' => ['array:service_id,amount'],
            'services.*.service_id' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'tip' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->user()->isAdmin()) {
            $rules += [
                'barber_id' => ['required', 'integer', Rule::exists('users', 'id')
                    ->where('role', UserRole::Barber->value)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')],
                'services.*.amount' => ['required', 'integer', 'min:0', 'max:10000000'],
                'performed_at' => ['nullable', 'date', 'before_or_equal:now'],
            ];
        }

        return $rules;
    }

    /**
     * The validated data, typed for the RegisterTransaction action.
     *
     * @return array{barber_id: int|null, services: list<array{service_id: int, amount: int|null}>, payment_method_id: int, tip: int, notes: string|null, performed_at: string|null}
     */
    public function transactionData(): array
    {
        $isAdmin = $this->user()->isAdmin();

        return [
            'barber_id' => $isAdmin ? $this->integer('barber_id') : null,
            'services' => array_values(array_map(fn (array $service) => [
                'service_id' => (int) $service['service_id'],
                'amount' => $isAdmin ? (int) $service['amount'] : null,
            ], $this->validated('services'))),
            'payment_method_id' => $this->integer('payment_method_id'),
            'tip' => $this->integer('tip'),
            'notes' => $this->filled('notes') ? $this->string('notes')->toString() : null,
            'performed_at' => $isAdmin && $this->filled('performed_at') ? $this->string('performed_at')->toString() : null,
        ];
    }
}
