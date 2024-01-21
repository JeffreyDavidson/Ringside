<?php

declare(strict_types=1);

namespace App\Livewire\Wrestler\Index;

use App\Models\Wrestler;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Form;

class Filters extends Form
{
    #[Url]
    public FilterStatus $status = FilterStatus::All;

    public function statuses(): Collection
    {
        return collect(FilterStatus::cases())->map(function ($status) {
            $count = $this->applyStatus(
                Wrestler::query(),
                $status,
            )->count();

            return [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => $count,
            ];
        });
    }

    public function apply($query)
    {
        $query = $this->applyStatus($query);

        return $query;
    }

    public function applyStatus($query, $status = null)
    {
        $status = $status ?? $this->status;

        if ($status === FilterStatus::All) {
            return $query;
        }

        return $query->where('status', $status);
    }
}
