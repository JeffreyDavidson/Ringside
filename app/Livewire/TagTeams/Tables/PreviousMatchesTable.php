<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousMatchesTable extends BasePreviousMatchesTable
{
    /**
     * Tag Team to use for component.
     */
    public ?int $tagTeamId;

    protected string $databaseTableName = 'event_matches';

    protected string $resourceName = 'matches';

    /**
     * @return Builder<EventMatch>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new Exception("You didn't specify a tag team");
        }

        $tagTeam = TagTeam::find($this->tagTeamId);

        return EventMatch::query()
            ->with(['titles', 'result.winner', 'result.decision'])
            ->withWhereHas('competitors', function (Builder $query) use ($tagTeam): void {
                $query->whereMorphedTo('competitor', $tagTeam);
            })
            ->withWhereHas('event', function (Builder $query): void {
                $query->whereNotNull('date')->where('date', '<', now()->toDateString());
            })
            ->orderByDesc('date');
    }
}
