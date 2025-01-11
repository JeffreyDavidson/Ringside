<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Builders\ManagerBuilder;
use App\Enums\ManagerStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasFirstEmploymentDateColumn;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasFirstEmploymentDateFilter;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Manager;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class ManagersTable extends BaseTableWithActions
{
    use HasFirstEmploymentDateColumn, HasFirstEmploymentDateFilter, HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'managers';

    protected string $routeBasePath = 'managers';

    protected string $resourceName = 'managers';

    public function builder(): ManagerBuilder
    {
        return Manager::query()
            ->with('firstEmployment')
            ->oldest('last_name')
            ->when($this->getAppliedFilterWithValue('Status'), fn ($query, $status) => $query->where('status', $status));
    }

    public function configure(): void {}

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('managers.name'), 'full_name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
            $this->getDefaultFirstEmploymentDateColumn(),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        $statuses = collect(ManagerStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            $this->getDefaultFirstEmploymentDateFilter(),
        ];
    }

    public function delete(Manager $manager): void
    {
        $this->deleteModel($manager);
    }
}
