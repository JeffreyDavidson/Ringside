<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasNewActivations
{
    public function latestCurrentActivation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->activations()->one()->ofMany([
            'started_at' => 'max',
        ], function (\Illuminate\Database\Eloquent\Builder $query) {
            $query->whereNull('ended_at')
                ->orWhere('ended_at', '>=', now());
        });
    }

    public function getLatestCurrentActivationStartDate()
    {
        return ! is_null($this->latestCurrentActivation) ? $this->latestCurrentActivation->started_at->format('Y-m-d') : 'N/A';
    }

    public function latestActivation()
    {
        return $this->activations()->one()->ofMany('started_at', 'max');
    }

    public function getLatestActivationStartDate()
    {
        return ! is_null($this->latestActivation) ? $this->latestActivation->started_at->format('Y-m-d') : 'N/A';
    }

    public function earliestActivation()
    {
        return $this->activations()->one()->ofMany('started_at', 'min');
    }

    public function getEarliestActivationStartDate()
    {
        return ! is_null($this->earliestActivation) ? $this->earliestActivation->started_at->format('Y-m-d') : 'N/A';
    }
}
