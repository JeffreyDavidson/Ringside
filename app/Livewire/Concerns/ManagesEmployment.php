<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait ManagesEmployment
{
    protected function handleEmploymentCreation(): void
    {
        if (! empty($this->employment_date)) {
            $this->formModel->employments()->create([
                'started_at' => $this->employment_date,
            ]);
        }
    }
}
