<?php

declare(strict_types=1);

namespace App\Livewire\Wrestler\Index;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Form;

class Filters extends Form
{
    #[Url]
    public FilterStatus $status = FilterStatus::All;

    public function statuses()
    {
        return collect(FilterStatus::cases())->map(function ($status) {
            $count = $this->applyProducts(
                $this->applyRange(
                    $this->applyStatus(
                        $this->store->orders(),
                        $status,
                    )
                )
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
        $query = $this->applyProducts($query);
        $query = $this->applyStatus($query);
        $query = $this->applyRange($query);

        return $query;
    }

    public function applyProducts($query)
    {
        return $query->whereIn('product_id', $this->selectedProductIds);
    }

    public function applyStatus($query, $status = null)
    {
        $status = $status ?? $this->status;

        if ($status === FilterStatus::All) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function applyRange($query)
    {
        if ($this->range === Range::All_Time) {
            return $query;
        }

        if ($this->range === Range::Custom) {
            $start = Carbon::createFromFormat('Y-m-d', $this->start);
            $end = Carbon::createFromFormat('Y-m-d', $this->end);

            return $query->whereBetween('ordered_at', [$start, $end]);
        }

        return $query->whereBetween('ordered_at', $this->range->dates());
    }
}
