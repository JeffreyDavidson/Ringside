<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Builders\ManagerBuilder;
use App\Enums\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Manager;
use App\View\Columns\FirstEmploymentDateColumn;
use App\View\Filters\FirstEmploymentFilter;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class ManagersTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'managers';

    protected string $routeBasePath = 'managers';

    protected string $resourceName = 'managers';

    /**
     * @return ManagerBuilder<Manager>
     */
    public function builder(): ManagerBuilder
    {
        return Manager::query()
            ->with('firstEmployment')
            ->oldest('last_name');
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
            FirstEmploymentDateColumn::make(__('employments.started_at')),
        ];
    }

    /**
     * Undocumented function
     *
     * @return array<int, Filter>
     */
    public function filters(): array
    {
        /** @var array<string, string> $statuses */
        $statuses = collect(EmploymentStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'managers_employments.started_at', 'managers_employments.ended_at'),
        ];
    }

    public function delete(Manager $manager): void
    {
        $this->deleteModel($manager);
    }
}
