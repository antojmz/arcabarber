<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Service|null $service */
        $service = $this->route('service');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Service::class)->ignore($service?->id)],
            'base_price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('Ya existe un servicio con ese nombre. Si fue eliminado, reactívalo desde la lista de eliminados.'),
        ];
    }
}
