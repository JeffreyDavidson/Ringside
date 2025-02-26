<?php

declare(strict_types=1);

namespace App\Livewire\Users\Tables;

use App\Builders\UserBuilder;
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
            ->with('latestAuthentication')
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
                        'model' => $row
                    ])
                )->html()
                ->searchable(),
            Column::make(__('users.role'), 'role')
                ->format(fn($value) => $value->name),
            $this->getDefaultStatusColumn(),
            Column::make(__('users.email'), 'email')
                ->searchable(),
            Column::make(__('users.phone'), 'phone_number')
                ->label(fn ($row, Column $column) => !is_null($row) ? $row->getFormattedPhoneNumber() : ''),
            Column::make(__('users.latestLogin'), 'latestAuthentication.login_at')
                ->format(fn($value) => !is_null($value) ? Carbon::parse($value)->diffForHumans() : '-'),
            Column::make(__('users.location'), 'latestAuthentication.location')
                ->label(
                    fn ($row, Column $column) => $row->latestAuthentication?->location['country'] ? view('components.tables.columns.country')->with([
                        'country' => $row->latestAuthentication?->location['country']
                    ]) : '-'
                )->html()
                // ->label(fn ($row, Column $column) => $row->latestAuthentication?->location['country'] ?? '-')
                ->searchable(),
        ];
    }
}
