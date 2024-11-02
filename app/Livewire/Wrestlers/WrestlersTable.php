<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Builders\WrestlerBuilder;
use App\Enums\WrestlerStatus;
use App\Livewire\Concerns\BaseTableTrait;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class WrestlersTable extends DataTableComponent
{
    use BaseTableTrait;

    protected string $databaseTableName = "wrestlers";

    protected string $routeBasePath = 'wrestlers';

    public function builder(): WrestlerBuilder
    {
        return Wrestler::query()
            ->with('currentEmployment')
            ->when($this->getAppliedFilterWithValue('Status'), fn ($query, $status) => $query->where('status', $status));
        ;
    }

    public function configure(): void
    {
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable(),
            Column::make(__('wrestlers.name'), 'name')
                ->sortable()
                ->searchable(),
            Column::make(__('wrestlers.status'), 'status')
                ->view('components.tables.columns.status-column'),
            Column::make(__('wrestlers.height'), 'height'),
            Column::make(__('wrestlers.weight'), 'weight'),
            Column::make(__('wrestlers.hometown'), 'hometown'),
            Column::make(__('employments.start_date'), 'currentEmployment.started_at')
                ->label(fn ($row, Column $column) => $row->currentEmployment?->started_at->format('Y-m-d') ?? 'TBD'),
        ];
    }

    public function filters(): array
    {
        $statuses = collect(WrestlerStatus::cases())->pluck('name', 'value')->toArray();

        return [
            SelectFilter::make('Status', 'status')
                ->options(['' => 'All'] + $statuses)
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('status', $value);
                }),
        ];
    }
}
