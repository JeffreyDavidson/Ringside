<?php

declare(strict_types=1);

namespace App\Livewire\Referees\Tables;

use App\Builders\RefereeBuilder;
use App\Enums\RefereeStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasFirstEmploymentDateColumn;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Referee;
use App\View\Filters\FirstEmploymentFilter;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class RefereesTable extends BaseTableWithActions
{
    use HasFirstEmploymentDateColumn, HasStatusColumn, HasStatusFilter;

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

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make(__('referees.name'), 'full_name')
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
        $statuses = collect(RefereeStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'referees_employments.started_at', 'referees_employments.ended_at'),
        ];
    }

    public function delete(Referee $referee): void
    {
        $this->deleteModel($referee);
    }
}
