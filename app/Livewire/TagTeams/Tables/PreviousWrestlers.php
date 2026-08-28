<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Builders\Roster\TagTeamMembershipBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends DataTableComponent<TagTeamWrestler> */
class PreviousWrestlers extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'wrestlers';

    protected string $databaseTableName = 'tag_teams_wrestlers';

    #[Locked]
    public ?int $tagTeamId;

    /** @return TagTeamMembershipBuilder<TagTeamWrestler> */
    public function builder(): TagTeamMembershipBuilder
    {
        if (! isset($this->tagTeamId)) {
            throw new LogicException('A tag team was not provided.');
        }

        return TagTeamWrestler::query()
            ->with('wrestler')
            ->forTagTeamId($this->tagTeamId)
            ->forHistory();
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('wrestlers.name'))
                ->title(fn (TagTeamWrestler $row) => $row->wrestler->name ?? 'Unknown')
                ->location(fn (TagTeamWrestler $row): string => $row->wrestler ? route('wrestlers.show', $row->wrestler) : '#'),
            DateColumn::make(__('tag-teams.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('tag-teams.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }

    protected function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_wrestlers.wrestler_id',
            'tag_teams_wrestlers.tag_team_id',
            'tag_teams_wrestlers.joined_at',
            'tag_teams_wrestlers.left_at',
        ]);
    }
}
