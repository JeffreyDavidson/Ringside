<?php

declare(strict_types=1);

namespace App\Livewire\Users\Tables;

use App\Builders\UserBuilder;
use App\Enums\Role;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\User;
use Illuminate\Support\Carbon;
use Rappasoft\LaravelLivewireTables\Views\Column;

class UsersTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'users';

    protected string $routeBasePath = 'users';

    protected string $resourceName = 'users';

    public function builder(): UserBuilder
    {
        return User::query()
            ->select('full_name', 'phone_number', 'email', 'status', 'avatar_path')
            ->oldest('last_name');
    }

    public function configure(): void {}

    /** @return array<Column> */
    public function columns(): array
    {
        return [
            Column::make(__('users.name'), 'full_name')
                ->label(
                    fn ($row, Column $column) => view('components.tables.columns.full-name')->with([
                        'model' => $row,
                    ])
                )->html()
                ->searchable(),
            Column::make(__('users.role'), 'role')
                ->format(fn (Role $value) => $value->name),
            $this->getDefaultStatusColumn(),
            Column::make(__('users.email'), 'email')
                ->searchable(),
            Column::make(__('users.phone'), 'phone_number')
                ->label(fn (User $row, Column $column) => $row->getFormattedPhoneNumber()),
        ];
    }
}
