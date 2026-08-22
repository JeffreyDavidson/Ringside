<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Table;

use App\Livewire\Table\Column;
use App\Livewire\Table\DataTableComponent;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;

/** @extends DataTableComponent<User> */
class TestDataTableComponent extends DataTableComponent
{
    /** @return Builder<User> */
    public function builder(): Builder
    {
        return (new User())->newQuery();
    }

    /** @return array<int, Column> */
    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->sortable(),
            Column::make('Email', 'email'),
        ];
    }

    /** @return array<int, Column> */
    protected function additionalColumns(): array
    {
        return [
            Column::make('Created At', 'created_at'),
        ];
    }
}
