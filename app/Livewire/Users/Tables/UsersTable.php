<?php

declare(strict_types=1);

namespace App\Livewire\Users\Tables;

use App\Builders\UserBuilder;
use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\User;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class UsersTable extends DataTableComponent
{
    use BaseTableTrait, HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'users';

    protected string $routeBasePath = 'users';

    protected string $resourceName = 'users';

    public function builder(): UserBuilder
    {
        return User::query()
            ->oldest('last_name');
    }

    public function configure(): void
    {
        $this->showActionColumn = false;
    }

    /** @return array<Column> */
    public function columns(): array
    {
        return [
            Column::make(__('users.name'), 'full_name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
            Column::make(__('users.email'), 'email')
                ->searchable(),
        ];
    }
}
