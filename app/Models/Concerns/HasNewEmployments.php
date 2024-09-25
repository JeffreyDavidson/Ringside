<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasNewEmployments
{
    public function latestCurrentEmployment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->employments()->one()->ofMany([
            'started_at' => 'max',
        ], function (\Illuminate\Database\Eloquent\Builder $query) {
            $query->whereNull('ended_at')
                ->orWhere('ended_at', '>=', now());
        });
    }

    public function getLatestCurrentEmploymentStartDate()
    {
        return ! is_null($this->latestCurrentEmployment) ? $this->latestCurrentEmployment->started_at->format('Y-m-d') : 'N/A';
    }

    public function latestEmployment()
    {
        return $this->employments()->one()->ofMany('started_at', 'max');
    }

    public function getLatestEmploymentStartDate()
    {
        return ! is_null($this->latestEmployment) ? $this->latestEmployment->started_at->format('Y-m-d') : 'N/A';
    }

    public function earliestEmployment()
    {
        return $this->employments()->one()->ofMany('started_at', 'min');
    }

    public function getEarliestEmploymentStartDate()
    {
        return ! is_null($this->earliestEmployment) ? $this->earliestEmployment->started_at->format('Y-m-d') : 'N/A';
    }
}
