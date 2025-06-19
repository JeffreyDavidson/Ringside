<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\EventMatch;
use App\Models\EventMatchCompetitor;
use App\Models\Referee;
use App\Models\Title;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\ArrayColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

abstract class BasePreviousMatchesTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'events_matches';

    protected string $resourceName = 'matches';

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'events_matches.event_id as event_id',
        ]);
    }

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('events.name'), 'event.name')
                ->title(fn (Model $row) => $row->event->name)
                ->location(fn (Model $row) => route('events.show', $row->event)),
            DateColumn::make(__('events.date'), 'event.date')
                ->inputFormat('Y-m-d H:i:s')
                ->outputFormat('Y-m-d')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('event-matches.referees'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->referees))
                ->outputFormat(function (int $index, Referee $value): string {
                    return '<a href="'.route('referees.show', $value->id).'">'.$value->full_name.'</a>';
                })
                ->separator(', ')
                ->emptyValue('N/A'),
            ArrayColumn::make(__('event-matches.competitors'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->competitors))
                ->outputFormat(function (int $index, EventMatchCompetitor $value): string {
                    $competitor = $value->getCompetitor();
                    $type = str($competitor->getMorphClass())->kebab()->plural();

                    return '<a href="'.route($type.'.show', $competitor->id).'">'.$competitor->name.'</a>';
                })
                ->separator('<br />'),
            ArrayColumn::make(__('event-matches.titles'))
                ->data(fn (mixed $value, EventMatch $row) => ($row->titles))
                ->outputFormat(fn (int $index, Title $value): string => '<a href="'.route('titles.show', $value->id).'">'.$value->name.'</a>')
                ->separator('<br />')
                ->emptyValue('N/A'),
            Column::make(__('event-matches.result'))
                ->label(function (EventMatch $row): string {
                    if ($row->result) {
                        $winner = $row->result?->getWinner();
                        $type = str($winner->getMorphClass())->kebab()->plural();

                        return '<a href="'.route($type.'.show', $winner->id).'">'.$winner->name.'</a> by '.$row->result?->decision->name;
                    }

                    return 'N/A';
                })->html(),
        ];
    }
}
