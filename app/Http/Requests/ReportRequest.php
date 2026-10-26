<?php

namespace App\Http\Requests;

use App\Enums\ClosingPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::enum(ClosingPeriod::class)],
            'date' => ['nullable', 'date'],
            'barber_id' => ['nullable', 'integer'],
        ];
    }

    public function period(): ClosingPeriod
    {
        return ClosingPeriod::tryFrom((string) $this->validated('period')) ?? ClosingPeriod::Daily;
    }

    public function referenceDate(): CarbonImmutable
    {
        $date = $this->validated('date');

        return $date ? CarbonImmutable::parse($date) : CarbonImmutable::today();
    }
}
