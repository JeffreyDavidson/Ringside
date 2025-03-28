<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\WrestlerBuilder;
use App\Enums\EmploymentStatus;
use App\Livewire\Base\Tables\BaseTableWithActions;
use App\Livewire\Concerns\Columns\HasStatusColumn;
use App\Livewire\Concerns\Filters\HasStatusFilter;
use App\Models\Wrestler;
use App\View\Columns\FirstEmploymentDateColumn;
use App\View\Filters\FirstEmploymentFilter;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class WrestlersTable extends BaseTableWithActions
{
    use HasStatusColumn, HasStatusFilter;

    protected string $databaseTableName = 'wrestlers';

    protected string $routeBasePath = 'wrestlers';

    protected string $resourceName = 'wrestlers';

    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->with('currentEmployment');
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
            FirstEmploymentDateColumn::make(__('employments.started_at')),
        ];
    }

    /**
     * @return array<int, Filter>
     **/
    public function filters(): array
    {
        /** @var array<string, string> $statuses */
        $statuses = collect(EmploymentStatus::cases())->pluck('name', 'value')->toArray();

        return [
            $this->getDefaultStatusFilter($statuses),
            FirstEmploymentFilter::make('Employment Date')->setFields('employments', 'wrestlers_employments.started_at', 'wrestlers_employments.ended_at'),
        ];
    }

    public function delete(Wrestler $wrestler): void
    {
        $this->deleteModel($wrestler);
    }
}
