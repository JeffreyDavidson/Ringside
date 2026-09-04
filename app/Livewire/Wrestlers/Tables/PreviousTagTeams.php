<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Builders\Roster\TagTeamMembershipBuilder;
use App\Livewire\Base\Tables\BasePreviousTagTeamsTable;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;

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

    /** @return TagTeamMembershipBuilder<TagTeamWrestler> */
    public function builder(): TagTeamMembershipBuilder
    {
        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        return TagTeamWrestler::query()
            ->forWrestlerId($wrestlerId)
            ->with('tagTeam.wrestlerMemberships.wrestler')
            ->forHistory();
    }

    /**
     * Configure additional query selections and table settings.
     *
     * Adds the tag team ID from the pivot table to the select statement
     * to ensure proper data retrieval for the table display. This allows
     * the table to access both the pivot data and related tag team information.
     */
    protected function configure(): void
    {
        $wrestlerId = $this->requireContextId($this->wrestlerId ?? null, 'wrestler');

        Gate::authorize('view', Wrestler::query()->findOrFail($wrestlerId));

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

        return $partner ? $this->routeResolver->urlFor($partner) : '#';
    }

    /**
     * Get the partner wrestler for the given tag team relationship.
     */
    private function getPartner(TagTeamWrestler $row): ?Wrestler
    {
        $partnerMembership = $row->tagTeam?->wrestlerMemberships->first(
            fn (TagTeamWrestler $membership): bool => $membership->wrestler_id !== $row->wrestler_id
                && $membership->joined_at->lte($row->left_at ?? now())
                && ($membership->left_at === null || $membership->left_at->gte($row->joined_at)),
        );

        return $partnerMembership?->wrestler;
    }
}
