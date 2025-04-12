<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class EventDateCanBeChanged implements ValidationRule
{
    public function __construct(protected ?Event $model) {}

    /**
     * Determine if the validation rule passes.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->model) {
            if ($this->model->hasPastDate()) {
                $fail('events.validation.has_past_date');
            }
        }
    }
}
