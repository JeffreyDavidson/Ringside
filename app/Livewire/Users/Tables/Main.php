<?php

declare(strict_types=1);

namespace App\Livewire\Users\Tables;

use App\Builders\Users\UserBuilder;
use App\Enums\Users\Role;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Table\Column;
use App\Models\Users\User;

class Main extends BaseTable
{
    protected bool $showActionColumn = true;

    protected string $databaseTableName = 'users';

    protected string $routeBasePath = 'users';

    protected string $resourceName = 'users';

    /** @return UserBuilder<User> */
    public function builder(): UserBuilder
    {
        return User::query()
            ->select('*')
            ->oldest('last_name');
    }

    public function configure(): void {}

    /** @return array<Column> */
    public function columns(): array
    {
        return [
            Column::make(__('users.name'), 'full_name')
                ->searchable(function (Builder $builder, string $searchTerm) {
                    $builder->whereNameMatches($searchTerm);
                }),
            Column::make(__('users.role'), 'role')
                ->format(fn (Role $value) => $value->name),
            Column::make(__('core.status'), 'status')
                ->label(fn (User $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            Column::make(__('users.email'), 'email')
                ->searchable(),
            Column::make(__('users.phone'), 'phone_number')
                ->label(fn (User $row, Column $column): string => $row->formatted_phone_number),
        ];
    }
}
