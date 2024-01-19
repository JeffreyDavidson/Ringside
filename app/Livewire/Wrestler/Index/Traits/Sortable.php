<?php

declare(strict_types=1);

namespace App\Livewire\Wrestler\Index\Traits;

use Livewire\Attributes\Url;

trait Sortable
{
    #[Url]
    public array $sortCol = [];

    #[Url]
    public bool $sortAsc = false;

    public function sortBy($column): void
    {
        if ($this->sortCol === $column) {
            $this->sortAsc = ! $this->sortAsc;
        } else {
            $this->sortCol = $column;
            $this->sortAsc = false;
        }
    }

    protected function applySorting($query)
    {
        if ($this->sortCol) {
            $column = match ($this->sortCol) {
                'name' => 'name',
                default => throw new \Exception('Unexpected sort column value'),
            };

            $query->orderBy($column, $this->sortAsc ? 'asc' : 'desc');
        }

        return $query;
    }
}
