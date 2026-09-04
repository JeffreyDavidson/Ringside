<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Builders\Roster\StableMembershipBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Support\RosterResourceRouteResolver;
use App\Livewire\Table\Column;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

/** @extends DataTableComponent<StableTagTeam> */
class PreviousTagTeams extends DataTableComponent
{
    use ShowTableTrait;

    protected string $resourceName = 'tag teams';

    protected string $databaseTableName = 'stables_tag_teams';

    #[Locked]
    public ?int $stableId;

    protected RosterResourceRouteResolver $routeResolver;

    public function boot(RosterResourceRouteResolver $routeResolver): void
    {
        $this->routeResolver = $routeResolver;
    }

    /** @return StableMembershipBuilder<StableTagTeam> */
    public function builder(): StableMembershipBuilder
    {
        $stableId = $this->requireContextId($this->stableId ?? null, 'stable');

        return StableTagTeam::query()
            ->with('tagTeam')
            ->forStableId($stableId)
            ->forHistory();
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            LinkColumn::make(__('tag-teams.name'))
                ->title(fn (StableTagTeam $row) => $row->tagTeam->name ?? 'Unknown')
                ->location(fn (StableTagTeam $row): string => $row->tagTeam ? $this->routeResolver->urlFor($row->tagTeam) : '#'),
            DateColumn::make(__('stables.date_joined'), 'joined_at')
                ->outputFormat('Y-m-d'),
            DateColumn::make(__('stables.date_left'), 'left_at')
                ->outputFormat('Y-m-d'),
        ];
    }

    protected function configure(): void
    {
        $stableId = $this->requireContextId($this->stableId ?? null, 'stable');

        Gate::authorize('view', Stable::query()->findOrFail($stableId));

        $this->addAdditionalSelects([
            'stables_tag_teams.tag_team_id',
            'stables_tag_teams.stable_id',
            'stables_tag_teams.joined_at',
            'stables_tag_teams.left_at',
        ]);
    }
}
