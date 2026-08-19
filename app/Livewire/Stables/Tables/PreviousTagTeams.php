<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\Stables\StableTagTeam;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use LogicException;

/** @extends DataTableComponent<StableTagTeam> */
class PreviousTagTeams extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'tag teams';

    protected string $databaseTableName = 'stables_tag_teams';

    #[Locked]
    public ?int $stableId;

    /**
     * @return Builder<StableTagTeam>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new LogicException('A stable was not provided.');
        }

        return StableTagTeam::query()
            ->with('tagTeam')
            ->forStableId($this->stableId)
            ->ended()
            ->mostRecentlyJoinedFirst();
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('tag-teams.name'))
                ->title(fn (StableTagTeam $row) => $row->tagTeam->name ?? 'Unknown')
                ->location(fn (StableTagTeam $row): string => $row->tagTeam ? route('tag-teams.show', $row->tagTeam) : '#'),
            DateColumn::make(__('stables.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('stables.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_tag_teams.tag_team_id',
            'stables_tag_teams.stable_id',
            'stables_tag_teams.joined_at',
            'stables_tag_teams.left_at',
        ]);
    }
}
