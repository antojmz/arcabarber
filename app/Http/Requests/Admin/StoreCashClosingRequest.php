<?php

namespace App\Http\Requests\Admin;

use App\Enums\ClosingPeriod;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashClosingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::enum(ClosingPeriod::class)],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'barber_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::Barber->value)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
