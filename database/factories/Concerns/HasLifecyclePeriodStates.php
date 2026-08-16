<?php

declare(strict_types=1);

namespace Database\Factories\Concerns;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

trait HasLifecyclePeriodStates
{
    public function current(): static
    {
        return $this->state(fn (): array => ['ended_at' => null]);
    }

    public function started(Carbon $startedAt): static
    {
        return $this->state(fn (): array => ['started_at' => $startedAt]);
    }

    public function ended(Carbon $endedAt): static
    {
        return $this->state(function (array $attributes) use ($endedAt): array {
            $startedAt = $this->parseLifecycleDate($attributes['started_at'] ?? null);

            if ($endedAt->isBefore($startedAt)) {
                throw new InvalidArgumentException('A lifecycle period cannot end before it starts.');
            }

            return ['ended_at' => $endedAt];
        });
    }

    private function parseLifecycleDate(mixed $value): Carbon
    {
        if ($value instanceof DateTimeInterface || is_string($value) || is_int($value) || is_float($value)) {
            return Carbon::parse($value);
        }

        throw new InvalidArgumentException('A lifecycle period requires a valid start date.');
    }
}
