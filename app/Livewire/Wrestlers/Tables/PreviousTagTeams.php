<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousTagTeamsTable;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use LogicException;

/**
 * Livewire table component for displaying a wrestler's previous tag team memberships.
 *
 * This component shows the historical tag teams that a wrestler was previously
 * a member of (where they have left). It extends the base previous tag teams
 * table to provide wrestler-specific filtering and data retrieval.
 *
 * The table displays tag teams ordered by when the wrestler joined them,
 * showing only completed memberships (where left_at is not null).
 */
class PreviousTagTeams extends BasePreviousTagTeamsTable
{
    /**
     * The ID of the wrestler whose previous tag teams should be displayed.
     *
     * This property must be set when the component is mounted to filter
     * the tag teams to only those the specified wrestler was a member of.
     *
     * @var int|null The wrestler's ID, or null if not set
     */
    #[Locked]
    public ?int $wrestlerId;

    /**
     * The database table name for the main query.
     *
     * @var string The name of the tag_teams_wrestlers pivot table
     */
    public string $databaseTableName = 'tag_teams_wrestlers';

    /**
     * Build the query for retrieving the wrestler's previous tag teams.
     *
     * Creates a query using the TagTeamWrestler pivot model to find all
     * tag team memberships where the wrestler has left (left_at is not null).
     * Results are ordered by join date in descending order to show the
     * most recent previous memberships first.
     *
     *
     * @throws LogicException If wrestlerId is not set
     * @return Builder<TagTeamWrestler> Query builder for tag team wrestler pivot records
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new LogicException('A wrestler was not provided.');
        }

        return TagTeamWrestler::query()
            ->forWrestlerId($this->wrestlerId)
            ->ended()
            ->mostRecentlyJoinedFirst();
    }

    /**
     * Configure additional query selections and table settings.
     *
     * Adds the tag team ID from the pivot table to the select statement
     * to ensure proper data retrieval for the table display. This allows
     * the table to access both the pivot data and related tag team information.
     */
    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_wrestlers.tag_team_id',
        ]);
    }

    /**
     * Get the partner wrestler name for the given tag team relationship.
     */
    protected function getPartnerName(TagTeamWrestler $row): string
    {
        $partner = $this->getPartner($row);

        return $partner ? $partner->name : 'Unknown';
    }

    /**
     * Get the route to the partner wrestler for the given tag team relationship.
     */
    protected function getPartnerRoute(TagTeamWrestler $row): string
    {
        $partner = $this->getPartner($row);

        return $partner ? route('wrestlers.show', $partner) : '#';
    }

    /**
     * Get the partner wrestler for the given tag team relationship.
     */
    private function getPartner(TagTeamWrestler $row): ?Wrestler
    {
        $partnerRecord = TagTeamWrestler::query()
            ->forTagTeamId($row->tag_team_id)
            ->excludingWrestlerId($row->wrestler_id)
            ->overlappingPeriod($row->joined_at, $row->left_at ?? now())
            ->with('wrestler')
            ->first();

        return $partnerRecord?->wrestler;
    }
}
