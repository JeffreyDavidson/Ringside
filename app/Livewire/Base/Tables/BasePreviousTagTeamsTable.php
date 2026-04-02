<?php

declare(strict_types=1);

namespace App\Livewire\Base\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\TagTeams\TagTeamWrestler;

abstract class BasePreviousTagTeamsTable extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'tag teams';

    protected string $databaseTableName;

    public function configure(): void {}

    /**
     * Get the partner wrestler name for the given tag team relationship.
     */
    abstract protected function getPartnerName(TagTeamWrestler $row): string;

    /**
     * Get the route to the partner wrestler for the given tag team relationship.
     */
    abstract protected function getPartnerRoute(TagTeamWrestler $row): string;

    /**
     * Undocumented function
     *
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('tag-teams.name'))
                ->title(fn (TagTeamWrestler $row) => $row->tagTeam->name)
                ->location(fn (TagTeamWrestler $row) => route('tag-teams.show', $row->tagTeam)),
            LinkColumn::make(__('tag-teams.partner'))
                ->title(fn (TagTeamWrestler $row) => $this->getPartnerName($row))
                ->location(fn (TagTeamWrestler $row) => $this->getPartnerRoute($row)),
            DateColumn::make(__('stables.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('stables.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }
}
