<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Builders\WrestlerBuilder;
use App\Models\Wrestler;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class WrestlersTable extends DataTableComponent
{
    public function builder(): WrestlerBuilder
    {
        return Wrestler::query();
        // ->with('employments:id,started_at')->withWhereHas('employments', function ($query) {
        //     $query->where('started_at', '<=', now())->whereNull('ended_at')->limit(1);
        // });
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setSearchPlaceholder('search wrestlers')
            ->setColumnSelectDisabled()
            ->setPaginationEnabled();
    }

    public function columns(): array
    {
        return [
            Column::make(__('wrestlers.name'), 'name')
                ->sortable()
                ->searchable(),
            Column::make(__('wrestlers.status'), 'status')
                ->view('status'),
            Column::make(__('wrestlers.height'), 'height'),
            Column::make(__('wrestlers.weight'), 'weight'),
            Column::make(__('wrestlers.hometown'), 'hometown'),
            // Column::make(__('employments.start_date'), 'started_at')
            //     ->label(fn ($row, Column $column) => $row->employments->first()->started_at->format('Y-m-d')),
            Column::make(__('core.actions'), 'actions')
                ->label(
                    fn ($row, Column $column) => view('components.livewire.datatables.action-column')->with(
                        [
                            'viewLink' => route('wrestlers.show', $row),
                            'editLink' => route('wrestlers.edit', $row),
                            'deleteLink' => route('wrestlers.destroy', $row),
                        ]
                    )
                )->html(),
        ];
    }
}
