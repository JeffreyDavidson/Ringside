<?php

declare(strict_types=1);

namespace App\Livewire\Wrestlers\Tables;

use App\Livewire\Base\Tables\BasePreviousTagTeamsTable;
use App\Models\TagTeams\TagTeamWrestler;
use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * Livewire table component for displaying a wrestler's previous tag team memberships.
 *
 * This component shows the historical tag teams that a wrestler was previously
 * a member of (where they have left). It extends the base previous tag teams
 * table to provide wrestler-specific filtering and data retrieval.
 *
 * The table displays tag teams ordered by when the wrestler joined them,
 * showing only completed memberships (where left_at is not null).
 *
 * @example
 * ```php
 * // In a Blade template
 * <livewire:wrestlers.tables.previous-tag-teams-table :wrestler-id="$wrestler->id" />
 *
 * // In a Livewire component
 * public function render()
 * {
 *     return view('livewire.wrestler.show', [
 *         'wrestler' => $this->wrestler,
 *     ]);
 * }
 * ```
 */
class PreviousTagTeamsTable extends BasePreviousTagTeamsTable
{
    /**
     * The ID of the wrestler whose previous tag teams should be displayed.
     *
     * This property must be set when the component is mounted to filter
     * the tag teams to only those the specified wrestler was a member of.
     *
     * @var int|null The wrestler's ID, or null if not set
     */
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
     * @return Builder<TagTeamWrestler> Query builder for tag team wrestler pivot records
     *
     * @throws Exception If wrestlerId is not set
     *
     * @example
     * ```php
     * // The query finds pivot records like:
     * // - TagTeamWrestler(wrestler_id: 1, tag_team_id: 5, joined_at: '1997-01-01', left_at: '1999-03-01')
     * // - TagTeamWrestler(wrestler_id: 1, tag_team_id: 8, joined_at: '1999-04-01', left_at: '2000-01-01')
     * // But excludes current memberships where left_at is null
     * ```
     */
    public function builder(): Builder
    {
        if (! isset($this->wrestlerId)) {
            throw new Exception("You didn't specify a wrestler");
        }

        return TagTeamWrestler::query()
            ->where('wrestler_id', $this->wrestlerId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    /**
     * Configure additional query selections and table settings.
     *
     * Adds the tag team ID from the pivot table to the select statement
     * to ensure proper data retrieval for the table display. This allows
     * the table to access both the pivot data and related tag team information.
     *
     *
     * @example
     * ```php
     * // Ensures the query selects:
     * // SELECT *, tag_team_id FROM tag_teams_wrestlers WHERE...
     * // This allows access to both pivot and tag team data in the table
     * ```
     */
    public function configure(): void
    {
        $this->addAdditionalSelects([
            'tag_teams_wrestlers.tag_team_id',
        ]);
    }
}
