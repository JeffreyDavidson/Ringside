<?php

declare(strict_types=1);

namespace App\Livewire\Referees;

use App\Builders\RefereeBuilder;
use App\Enums\RefereeStatus;
use App\Livewire\Concerns\BaseTableTrait;
use App\Livewire\Concerns\Columns\HasFirstEmploymentDateColumn;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasFirstEmploymentDateFilter;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Referee;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RefereesTable extends DataTableComponent
{
    use BaseTableTrait, HasFirstEmploymentDateColumn, HasFirstEmploymentDateFilter, HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'referees';

    protected string $routeBasePath = 'referees';

    protected string $resourceName = 'referees';

    public function builder(): RefereeBuilder
    {
        return Referee::query()
            ->with('firstEmployment')
            ->oldest('last_name')
            ->when($this->getAppliedFilterWithValue('Status'), fn ($query, $status) => $query->where('status', $status));
    }

    public function configure(): void {}

    public function columns(): array
    {
        return [
            Column::make(__('referees.name'), 'full_name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
            $this->getDefaultFirstEmploymentDateColumn(),
        ];
    }

    public function filters(): array
    {
        $statuses = collect(RefereeStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            $this->getDefaultFirstEmploymentDateFilter(),
        ];
    }
}
