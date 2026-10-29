<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicServiceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'barber_id' => ['required', 'integer', Rule::exists('users', 'id')
                ->where('role', UserRole::Barber->value)
                ->where('is_active', true)
                ->whereNull('deleted_at')],
            'service_ids' => ['required', 'array', 'min:1', 'max:20'],
            'service_ids.*' => ['required', 'integer', 'distinct', Rule::exists('services', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'tip' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Key used to throttle failed password attempts per barber and device.
     */
    public function throttleKey(): string
    {
        return 'public-service:'.$this->integer('barber_id').'|'.$this->ip();
    }
}
