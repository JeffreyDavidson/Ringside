<?php

declare(strict_types=1);

namespace App\Livewire\Users\Tables;

use App\Builders\Users\UserBuilder;
use App\Enums\Users\UserStatus;
use App\Livewire\Base\Tables\BaseTable;
use App\Livewire\Table\Column;
use App\Livewire\Table\Filter;
use App\Livewire\Table\Filters\SelectFilter;
use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;

/** @extends BaseTable<User> */
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

    protected function configure(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    /** @return array<Column> */
    public function columns(): array
    {
        return [
            Column::make(__('users.name'), 'full_name')
                ->searchable(function (UserBuilder $builder, string $searchTerm): void {
                    $builder->whereNameMatches($searchTerm);
                }),
            Column::make(__('users.role'), 'role')
                ->label(fn (User $row) => $row->role->name),
            Column::make(__('core.status'), 'status')
                ->label(fn (User $row) => $row->status->label())
                ->excludeFromColumnSelect(),
            Column::make(__('users.email'), 'email')
                ->searchable(),
            Column::make(__('users.phone'), 'phone_number')
                ->label(fn (User $row, Column $column): string => $row->phone_number?->formatted() ?? ''),
        ];
    }

    /** @return array<int, Filter> */
    public function filters(): array
    {
        return [
            SelectFilter::make(__('core.status'))
                ->options(UserStatus::filterOptions())
                ->filter(function (UserBuilder $builder, string $value): void {
                    $status = UserStatus::tryFrom($value);

                    if ($status !== null) {
                        $builder->whereStatus($status);
                    }
                }),
        ];
    }
}
