<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousEventsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'events';

    protected string $routeBasePath = 'events';

    protected string $resourceName = 'events';

    public ?int $venueId;

    public function builder(): Builder
    {
        if (! isset($this->venueId)) {
            throw new \Exception("You didn't specify a venue");
        }

        return Event::query()
            ->where('venue_id', $this->venueId)
            ->orderByDesc('date');
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
            LinkColumn::make(__('events.name'), 'name')
                ->title(fn ($row) => $row->name)
                ->location(fn ($row) => route('events.show', $row)),
            DateColumn::make(__('events.date'), 'date')
                ->outputFormat('Y-m-d'),
        ];
    }
}
