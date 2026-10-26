<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class BarberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $barber */
        $barber = $this->route('barber');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($barber?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'commission_rate' => ['required', 'numeric', 'between:0,100'],
            'is_active' => ['required', 'boolean'],
            'password' => [$barber ? 'nullable' : 'required', 'string', Password::default(), 'confirmed'],
        ];
    }
}
