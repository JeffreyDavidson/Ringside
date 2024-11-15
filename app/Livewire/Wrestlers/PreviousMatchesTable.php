<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'event_matches';

    protected string $resourceName = 'matches';

    /**
     * Wrestler to use for component.
     */
    public Wrestler $wrestler;

    /**
     * Set the Wrestler to be used for this component.
     */
    public function mount(Wrestler $wrestler): void
    {
        $this->wrestler = $wrestler;
    }

    public function builder(): Builder
    {
        return $this->wrestler
            ->previousMatches
            ->toQuery();
    }

    public function configure(): void
    {
    }

    public function columns(): array
    {
        return [
            LinkColumn::make(__('events.name'))
                ->title(fn ($row) => $row->name)
                ->location(fn ($row) => route('events.show', $row)),
            DateColumn::make(__('events.date'), 'date')
                ->outputFormat('Y-m-d H:i'),
            ArrayColumn::make(__('event-matches.competitors'))
                ->data(fn ($value, $row) => ($row->competitors))
                ->outputFormat(fn ($index, $value) => '<a href="' . route('wrestlers.show', $value->competitor->id) . '">' . $value->competitor->name . '</a>')
                ->separator('<br />'),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn ($value, $row) => ($row->titles))
                ->outputFormat(fn ($index, $value) => '<a href="' . route('titles.show', $value->id) . '">' . $value->name . '</a>')
                ->separator('<br />'),
            Column::make(__('event-matches.result'))
                ->label(fn ($row) => $row->result->winner->name.' by '.$row->result->decision->name),
        ];
    }
}
