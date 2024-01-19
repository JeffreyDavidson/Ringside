<?php

declare(strict_types=1);

namespace App\Livewire\Wrestler\Index\Traits;

use App\Builders\WrestlerBuilder;

trait Searchable
{
    public string $search = '';

    public function updatedSearchable($property): void
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }

    protected function applySearch($query): WrestlerBuilder
    {
        return $this->search === ''
            ? $query
            : $query
                ->where('name', 'like', '%'.$this->search.'%');
    }
}
