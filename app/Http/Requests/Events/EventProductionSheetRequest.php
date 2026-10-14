<?php

declare(strict_types=1);

namespace App\Http\Requests\Events;

use App\Enums\Crew\CrewRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventProductionSheetRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source' => ['nullable', Rule::in(['plan', 'live'])],
            'person' => ['nullable', 'integer', 'min:1'],
            'role' => ['nullable', Rule::enum(CrewRole::class)],
            'print' => ['nullable', 'boolean'],
        ];
    }
}
