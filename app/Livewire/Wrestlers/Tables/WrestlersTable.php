<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\WrestlerBuilder;
use App\Enums\WrestlerStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasFirstEmploymentDateColumn;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasFirstEmploymentDateFilter;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class WrestlersTable extends BaseTableWithActions
{
    use HasFirstEmploymentDateColumn, HasFirstEmploymentDateFilter, HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'wrestlers';

    protected string $routeBasePath = 'wrestlers';

    protected string $resourceName = 'wrestlers';

    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->with('currentEmployment')
            ->when(
                $this->getAppliedFilterWithValue('Employment'),
                fn (Builder $query, array $dateRange) => $query
                    ->whereDate('wrestler_employments.started_at', '>=', $dateRange['minDate'])
                    ->whereDate('wrestler_employments.ended_at', '<=', $dateRange['maxDate'])
            );
    }

    public function configure(): void {}

    /**
     * @return array<int, Column>
     **/
    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'name')
                ->searchable(),
            $this->getDefaultStatusColumn(),
            Column::make(__('wrestlers.height'), 'height'),
            Column::make(__('wrestlers.weight'), 'weight'),
            Column::make(__('wrestlers.hometown'), 'hometown'),
            $this->getDefaultFirstEmploymentDateColumn(),
        ];
    }

    /**
     * @return array<int, Filter>
     **/
    public function filters(): array
    {
        $statuses = collect(WrestlerStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            $this->getDefaultFirstEmploymentDateFilter(),
        ];
    }

    public function delete(Wrestler $wrestler): void
    {
        $this->deleteModel($wrestler);
    }
}
