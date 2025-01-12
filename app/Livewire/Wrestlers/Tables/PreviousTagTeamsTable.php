<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\TagTeamBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class PreviousTagTeamsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $databaseTableName = 'tag_teams';

    protected string $resourceName = 'tag teams';

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

    public function builder(): TagTeamBuilder
    {
        return TagTeam::query()
            ->withWhereHas('wrestlers', function ($query) {
                $query->whereIn('wrestler_id', [$this->wrestler->id]);
            });
    }

    public function configure(): void {}

    public function columns(): array
    {
        return [
            LinkColumn::make(__('tag-teams.name'))
                ->title(fn ($row) => $row->name)
                ->location(fn ($row) => route('tag-teams.show', $row)),
            LinkColumn::make(__('tag-teams.partner'))
                ->title(fn ($row) => $row->partner->name)
                ->location(fn ($row) => route('wrestlers.show', $row)),
            DateColumn::make(__('tag-teams.date_joined'), 'date_joined')
                ->outputFormat('Y-m-d H:i'),
            DateColumn::make(__('tag-teams.date_left'), 'date_left')
                ->outputFormat('Y-m-d H:i'),
        ];
    }
}
