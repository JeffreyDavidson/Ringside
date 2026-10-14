<?php

declare(strict_types=1);

namespace App\Rules\Events;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class PublicDoorsTime implements ValidationRule
{
    public function __construct(private readonly string $timezone, private readonly string $offset) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) || ! preg_match('/^[+-](?:0\d|1[0-4]):[0-5]\d$/', $this->offset)) {
            $fail(__('public-events.doors_invalid'));

            return;
        }
        $local = Carbon::createFromFormat('!Y-m-d\TH:iP', $value.$this->offset);
        if (! $local instanceof Carbon || $local->setTimezone($this->timezone)->format('Y-m-d\TH:iP') !== $value.$this->offset) {
            $fail(__('public-events.doors_invalid'));
        }
    }
}
