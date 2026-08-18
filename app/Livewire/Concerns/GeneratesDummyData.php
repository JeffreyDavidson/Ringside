<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait GeneratesDummyData
{
    public function fillDummyFields(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $this->populateDummyData();
    }

    abstract protected function populateDummyData(): void;

    protected function generateOptionalStartDate(
        string $format = 'Y-m-d H:i:s',
        float $probability = 0.8,
        string $minPeriod = 'now',
        string $maxPeriod = '+3 month',
    ): ?string {
        if (! fake()->boolean((int) ($probability * 100))) {
            return null;
        }

        return fake()->dateTimeBetween($minPeriod, $maxPeriod)->format($format);
    }

    protected function generateOptionalEmploymentDate(float $probability = 0.8): ?string
    {
        return $this->generateOptionalStartDate('Y-m-d', $probability);
    }
}
