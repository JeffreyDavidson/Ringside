<?php

declare(strict_types=1);

namespace Tests\Integration\Livewire\Table;

use App\Enums\Users\Role;
use App\Livewire\Table\Column;
use App\Livewire\Table\DataTableComponent;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
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
            Column::make('Name', 'first_name')->sortable()->searchable(),
            Column::make('Email', 'email'),
        ];
    }

    /** @return array<int, Filter> */
    public function filters(): array
    {
        return [
            SelectFilter::make('Role')
                ->options([
                    '' => 'All roles',
                    Role::Administrator->value => Role::Administrator->label(),
                    Role::Basic->value => Role::Basic->label(),
                ])
                ->filter(
                    /** @param Builder<User> $query */
                    function (Builder $query, string $role): void {
                        $query->where('role', $role);
                    },
                ),
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
