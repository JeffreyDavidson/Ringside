<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models\Events{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property Carbon|null $date
 * @property int|null $venue_id
 * @property string|null $preview
 * @property string|null $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Venue|null $venue
 * @property-read Collection<int, EventMatch> $matches
 * @method static \App\Builders\EventBuilder<static>|Event newModelQuery()
 * @method static \App\Builders\EventBuilder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static \App\Builders\EventBuilder<static>|Event past()
 * @method static \App\Builders\EventBuilder<static>|Event query()
 * @method static \App\Builders\EventBuilder<static>|Event scheduled()
 * @method static \App\Builders\EventBuilder<static>|Event unscheduled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 */
	class Event extends \Eloquent {}
}

namespace App\Models\Managers{
/**
 *
 *
 * @mixin \Eloquent
 * @implements Employable<ManagerEmployment, static>
 * @implements Injurable<ManagerInjury, static>
 * @implements Retirable<ManagerRetirement, static>
 * @implements Suspendable<ManagerSuspension, static>
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property-read string $full_name
 * @property EmploymentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ManagerEmployment|null $currentEmployment
 * @property-read ManagerEmployment|null $firstEmployment
 * @property-read ManagerEmployment|null $futureEmployment
 * @property-read ManagerEmployment|null $previousEmployment
 * @property-read Collection<int, ManagerEmployment> $employments
 * @property-read Collection<int, ManagerEmployment> $previousEmployments
 * @property-read ManagerInjury|null $currentInjury
 * @property-read ManagerInjury|null $previousInjury
 * @property-read Collection<int, ManagerInjury> $injuries
 * @property-read Collection<int, ManagerInjury> $previousInjuries
 * @property-read ManagerRetirement|null $currentRetirement
 * @property-read ManagerRetirement|null $previousRetirement
 * @property-read Collection<int, ManagerRetirement> $retirements
 * @property-read Collection<int, ManagerRetirement> $previousRetirements
 * @property-read ManagerSuspension|null $currentSuspension
 * @property-read ManagerSuspension|null $previousSuspension
 * @property-read Collection<int, ManagerSuspension> $suspensions
 * @property-read Collection<int, ManagerSuspension> $previousSuspensions
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, Wrestler> $currentWrestlers
 * @property-read Collection<int, Wrestler> $previousWrestlers
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, TagTeam> $currentTagTeams
 * @property-read Collection<int, TagTeam> $previousTagTeams
 * @property-read Stable|null $currentStable
 * @property-read Collection<int, Stable> $stables
 * @property-read Collection<int, Stable> $previousStables
 * @property int|null $user_id
 * @property-read mixed $display_name
 * @method static \App\Builders\ManagerBuilder<static>|Manager available()
 * @method static \App\Builders\ManagerBuilder<static>|Manager futureEmployed()
 * @method static \App\Builders\ManagerBuilder<static>|Manager injured()
 * @method static \App\Builders\ManagerBuilder<static>|Manager newModelQuery()
 * @method static \App\Builders\ManagerBuilder<static>|Manager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager onlyTrashed()
 * @method static \App\Builders\ManagerBuilder<static>|Manager query()
 * @method static \App\Builders\ManagerBuilder<static>|Manager released()
 * @method static \App\Builders\ManagerBuilder<static>|Manager retired()
 * @method static \App\Builders\ManagerBuilder<static>|Manager suspended()
 * @method static \App\Builders\ManagerBuilder<static>|Manager unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Manager withoutTrashed()
 */
	class Manager extends \Eloquent implements \App\Models\Contracts\CanBeAStableMember, \App\Models\Contracts\Employable, \App\Models\Contracts\HasDisplayName, \App\Models\Contracts\Injurable, \App\Models\Contracts\Retirable, \App\Models\Contracts\Suspendable {}
}

namespace App\Models\Managers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $manager_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manager|null $manager
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerEmployment query()
 */
	class ManagerEmployment extends \Eloquent {}
}

namespace App\Models\Managers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $manager_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manager|null $manager
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerInjury newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerInjury newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerInjury query()
 */
	class ManagerInjury extends \Eloquent {}
}

namespace App\Models\Managers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $manager_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manager|null $manager
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerRetirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerRetirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerRetirement query()
 */
	class ManagerRetirement extends \Eloquent {}
}

namespace App\Models\Managers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $manager_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manager|null $manager
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerSuspension query()
 */
	class ManagerSuspension extends \Eloquent {}
}

namespace App\Models\Matches{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $event_id
 * @property int $match_number
 * @property int $match_type_id
 * @property string|null $preview
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EventMatchCompetitor|null $pivot
 * @property-read EventMatchCompetitorsCollection<int, EventMatchCompetitor> $competitors
 * @property-read Event $event
 * @property-read MatchType|null $matchType
 * @property-read EventMatchResult|null $result
 * @property-read Collection<int, Referee> $referees
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, Title> $titles
 * @property-read Collection<int, Wrestler> $wrestlers
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatch query()
 */
	class EventMatch extends \Eloquent {}
}

namespace App\Models\Matches{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $event_match_id
 * @property string $competitor_type
 * @property int $competitor_id
 * @property int $side_number Numeric identifier for the side/team this competitor belongs to. Used to group competitors by side.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wrestler|TagTeam $competitor
 * @method static \App\Collections\EventMatchCompetitorsCollection<int, static> all($columns = ['*'])
 * @method static \App\Collections\EventMatchCompetitorsCollection<int, static> get($columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchCompetitor query()
 */
	class EventMatchCompetitor extends \Eloquent {}
}

namespace App\Models\Matches{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $event_match_id
 * @property string $winner_type
 * @property int $winner_id
 * @property int $match_decision_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MatchDecision $decision
 * @property-read Wrestler|TagTeam $winner
 * @property-read EventMatch $eventMatch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventMatchResult query()
 */
	class EventMatchResult extends \Eloquent {}
}

namespace App\Models\Matches{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchDecision query()
 */
	class MatchDecision extends \Eloquent {}
}

namespace App\Models\Matches{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int|null $number_of_sides
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MatchType query()
 */
	class MatchType extends \Eloquent {}
}

namespace App\Models\Referees{
/**
 *
 *
 * @mixin \Eloquent
 * @implements Employable<RefereeEmployment, static>
 * @implements Injurable<RefereeInjury, static>
 * @implements Retirable<RefereeRetirement, static>
 * @implements Suspendable<RefereeSuspension, static>
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property-read string $full_name
 * @property EmploymentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read RefereeEmployment|null $currentEmployment
 * @property-read RefereeEmployment|null $firstEmployment
 * @property-read RefereeEmployment|null $futureEmployment
 * @property-read RefereeEmployment|null $previousEmployment
 * @property-read Collection<int, RefereeEmployment> $employments
 * @property-read Collection<int, RefereeEmployment> $previousEmployments
 * @property-read RefereeInjury|null $currentInjury
 * @property-read RefereeInjury|null $previousInjury
 * @property-read Collection<int, RefereeInjury> $injuries
 * @property-read Collection<int, RefereeInjury> $previousInjuries
 * @property-read RefereeRetirement|null $currentRetirement
 * @property-read RefereeRetirement|null $previousRetirement
 * @property-read Collection<int, RefereeRetirement> $retirements
 * @property-read Collection<int, RefereeRetirement> $previousRetirements
 * @property-read RefereeSuspension|null $currentSuspension
 * @property-read RefereeSuspension|null $previousSuspension
 * @property-read Collection<int, RefereeSuspension> $suspensions
 * @property-read Collection<int, RefereeSuspension> $previousSuspensions
 * @property-read Collection<int, EventMatch> $matches
 * @property-read Collection<int, EventMatch> $previousMatches
 * @property-read mixed $display_name
 * @method static \App\Builders\RefereeBuilder<static>|Referee bookable()
 * @method static \App\Builders\RefereeBuilder<static>|Referee futureEmployed()
 * @method static \App\Builders\RefereeBuilder<static>|Referee injured()
 * @method static \App\Builders\RefereeBuilder<static>|Referee newModelQuery()
 * @method static \App\Builders\RefereeBuilder<static>|Referee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee onlyTrashed()
 * @method static \App\Builders\RefereeBuilder<static>|Referee query()
 * @method static \App\Builders\RefereeBuilder<static>|Referee released()
 * @method static \App\Builders\RefereeBuilder<static>|Referee retired()
 * @method static \App\Builders\RefereeBuilder<static>|Referee suspended()
 * @method static \App\Builders\RefereeBuilder<static>|Referee unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Referee withoutTrashed()
 */
	class Referee extends \Eloquent implements \App\Models\Contracts\Employable, \App\Models\Contracts\HasDisplayName, \App\Models\Contracts\Injurable, \App\Models\Contracts\Retirable, \App\Models\Contracts\Suspendable {}
}

namespace App\Models\Referees{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $referee_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Referee|null $referee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeEmployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeEmployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeEmployment query()
 */
	class RefereeEmployment extends \Eloquent {}
}

namespace App\Models\Referees{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $referee_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Referee|null $referee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeInjury newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeInjury newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeInjury query()
 */
	class RefereeInjury extends \Eloquent {}
}

namespace App\Models\Referees{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $referee_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Referee|null $referee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeRetirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeRetirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeRetirement query()
 */
	class RefereeRetirement extends \Eloquent {}
}

namespace App\Models\Referees{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $referee_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Referee|null $referee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefereeSuspension query()
 */
	class RefereeSuspension extends \Eloquent {}
}

namespace App\Models\Shared{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string|null $name
 * @property string|null $code
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|State query()
 */
	class State extends \Eloquent {}
}

namespace App\Models\Shared{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string $name
 * @property string $street_address
 * @property string $city
 * @property string $state
 * @property string $zipcode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Event> $events
 * @property-read Collection<int, Event> $previousEvents
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue withoutTrashed()
 */
	class Venue extends \Eloquent {}
}

namespace App\Models\Stables{
/**
 *
 *
 * @mixin \Eloquent
 * @implements HasActivityPeriods<StableActivityPeriod, static>
 * @implements Retirable<StableRetirement, static>
 * @property int $id
 * @property string $name
 * @property ActivationStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, StableActivityPeriod> $activations
 * @property-read StableMember|null $pivot
 * @property-read StableActivityPeriod|null $currentActivation
 * @property-read StableActivityPeriod|null $firstActivation
 * @property-read StableActivityPeriod|null $futureActivation
 * @property-read StableActivityPeriod|null $previousActivation
 * @property-read Collection<int, StableActivityPeriod> $previousActivations
 * @property-read StableRetirement|null $currentRetirement
 * @property-read StableRetirement|null $previousRetirement
 * @property-read Collection<int, StableRetirement> $retirements
 * @property-read Collection<int, StableRetirement> $previousRetirements
 * t
 * @property-read Collection<int, Manager> $managers
 * @property-read Collection<int, Manager> $currentManagers
 * @property-read Collection<int, Manager> $previousManagers
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, TagTeam> $currentTagTeams
 * @property-read Collection<int, TagTeam> $previousTagTeams
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, Wrestler> $currentWrestlers
 * @property-read Collection<int, Wrestler> $previousWrestlers
 * @property int|null $user_id
 * @method static \App\Builders\StableBuilder<static>|Stable activatedAfter(\Carbon\Carbon $date)
 * @method static \App\Builders\StableBuilder<static>|Stable activatedBefore(\Carbon\Carbon $date)
 * @method static \App\Builders\StableBuilder<static>|Stable active()
 * @method static \App\Builders\StableBuilder<static>|Stable activeDuring(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static \App\Builders\StableBuilder<static>|Stable currentlyActive()
 * @method static \App\Builders\StableBuilder<static>|Stable currentlyInactive()
 * @method static \App\Builders\StableBuilder<static>|Stable deactivatedAfter(\Carbon\Carbon $date)
 * @method static \App\Builders\StableBuilder<static>|Stable inactive()
 * @method static \App\Builders\StableBuilder<static>|Stable neverActivated()
 * @method static \App\Builders\StableBuilder<static>|Stable newModelQuery()
 * @method static \App\Builders\StableBuilder<static>|Stable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable onlyTrashed()
 * @method static \App\Builders\StableBuilder<static>|Stable query()
 * @method static \App\Builders\StableBuilder<static>|Stable retired()
 * @method static \App\Builders\StableBuilder<static>|Stable unactivated()
 * @method static \App\Builders\StableBuilder<static>|Stable withFutureActivation()
 * @method static \App\Builders\StableBuilder<static>|Stable withMultiplePeriods(int $minimumPeriods = 2)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stable withoutTrashed()
 */
	class Stable extends \Eloquent implements \App\Models\Contracts\HasActivityPeriods, \App\Models\Contracts\Retirable {}
}

namespace App\Models\Stables{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $stable_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stable|null $stable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableActivityPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableActivityPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableActivityPeriod query()
 */
	class StableActivityPeriod extends \Eloquent {}
}

namespace App\Models\Stables{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $stable_id
 * @property int $manager_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manager $manager
 * @property-read Stable $stable
}

namespace App\Models\Stables{
/**
 *
 *
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableMember query()
 */
	class StableMember extends \Eloquent {}
}

namespace App\Models\Stables{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $stable_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Stable|null $stable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableRetirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableRetirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableRetirement query()
 */
	class StableRetirement extends \Eloquent {}
}

namespace App\Models\Stables{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $stable_id
 * @property int $tag_team_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TagTeam $tagTeam
 * @property-read Stable $stable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableTagTeam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableTagTeam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableTagTeam query()
 */
	class StableTagTeam extends \Eloquent {}
}

namespace App\Models\Stables{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $stable_id
 * @property int $wrestler_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wrestler $wrestler
 * @property-read Stable $stable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StableWrestler query()
 */
	class StableWrestler extends \Eloquent {}
}

namespace App\Models\TagTeams{
/**
 *
 *
 * @mixin \Eloquent
 * @implements Bookable<EventMatchCompetitor>
 * @implements CanBeChampion<TitleChampionship>
 * @implements CanBeAStableMember<StableTagTeam, static>
 * @implements Employable<TagTeamEmployment, static>
 * @implements HasTagTeamWrestlers<static, TagTeamWrestler>
 * @implements Manageable<TagTeamManager, static>
 * @implements Retirable<TagTeamRetirement, static>
 * @implements Suspendable<TagTeamSuspension, static>
 * @property int $id
 * @property string $name
 * @property string|null $signature_move
 * @property EmploymentStatus $status
 * @property-read int $combined_weight
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TagTeamWrestler|TagTeamManager|null $pivot
 * @property-read TagTeamEmployment|null $currentEmployment
 * @property-read TagTeamEmployment|null $firstEmployment
 * @property-read TagTeamEmployment|null $futureEmployment
 * @property-read TagTeamEmployment|null $previousEmployment
 * @property-read Collection<int, TagTeamEmployment> $employments
 * @property-read Collection<int, TagTeamEmployment> $previousEmployments
 * @property-read TagTeamRetirement|null $currentRetirement
 * @property-read TagTeamRetirement|null $previousRetirement
 * @property-read Collection<int, TagTeamRetirement> $retirements
 * @property-read Collection<int, TagTeamRetirement> $previousRetirements
 * @property-read TagTeamSuspension|null $currentSuspension
 * @property-read TagTeamSuspension|null $previousSuspension
 * @property-read Collection<int, TagTeamSuspension> $suspensions
 * @property-read Collection<int, TagTeamSuspension> $previousSuspensions
 * @property-read Collection<int, Wrestler> $wrestlers
 * @property-read Collection<int, Wrestler> $currentWrestlers
 * @property-read Collection<int, Wrestler> $previousWrestlers
 * @property-read Collection<int, Manager> $managers
 * @property-read Collection<int, Manager> $currentManagers
 * @property-read Collection<int, Manager> $previousManagers
 * @property-read Stable|null $currentStable
 * @property-read Collection<int, Stable> $stables
 * @property-read Collection<int, Stable> $previousStables
 * @property-read Collection<int, EventMatch> $matches
 * @property-read Collection<int, EventMatch> $previousMatches
 * @property-read TitleChampionship|null $currentChampionship
 * @property-read Collection<int, TitleChampionship> $titleChampionships
 * @property-read Collection<int, TitleChampionship> $currentChampionships
 * @property-read Collection<int, TitleChampionship> $previousTitleChampionships
 * @property int|null $user_id
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam bookable()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam futureEmployed()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam newModelQuery()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeam onlyTrashed()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam query()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam released()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam retired()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam suspended()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam unbookable()
 * @method static \App\Builders\TagTeamBuilder<static>|TagTeam unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeam withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeam withoutTrashed()
 */
	class TagTeam extends \Eloquent implements \App\Models\Contracts\Bookable, \App\Models\Contracts\CanBeAStableMember, \App\Models\Contracts\CanBeChampion, \App\Models\Contracts\Employable, \App\Models\Contracts\HasTagTeamWrestlers, \App\Models\Contracts\Manageable, \App\Models\Contracts\Retirable, \App\Models\Contracts\Suspendable {}
}

namespace App\Models\TagTeams{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $tag_team_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TagTeam|null $tagTeam
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamEmployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamEmployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamEmployment query()
 */
	class TagTeamEmployment extends \Eloquent {}
}

namespace App\Models\TagTeams{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $tag_team_id
 * @property int $manager_id
 * @property Carbon $hired_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manager|null $manager
 * @property-read TagTeam|null $tagTeam
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamManager query()
 */
	class TagTeamManager extends \Eloquent {}
}

namespace App\Models\TagTeams{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $tag_team_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TagTeam|null $tagTeam
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamRetirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamRetirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamRetirement query()
 */
	class TagTeamRetirement extends \Eloquent {}
}

namespace App\Models\TagTeams{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $tag_team_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TagTeam|null $tagTeam
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension query()
 */
	class TagTeamSuspension extends \Eloquent {}
}

namespace App\Models\TagTeams{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $tag_team_id
 * @property int $wrestler_id
 * @property Carbon $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TagTeam|null $tagTeam
 * @property-read Wrestler|null $partner
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamWrestler newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamWrestler newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamWrestler query()
 */
	class TagTeamWrestler extends \Eloquent {}
}

namespace App\Models\Titles{
/**
 *
 *
 * @mixin \Eloquent
 * @implements HasActivityPeriods<TitleActivityPeriod, static>
 * @implements Retirable<\TitleRetirement, static>
 * @property int $id
 * @property string $name
 * @property ActivationStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TitleActivityPeriod|null $currentActivation
 * @property-read TitleActivityPeriod|null $firstActivation
 * @property-read TitleActivityPeriod|null $futureActivation
 * @property-read TitleActivityPeriod|null $previousActivation
 * @property-read Collection<int, TitleActivityPeriod> $activations
 * @property-read Collection<int, TitleActivityPeriod> $previousActivations
 * @property-read TitleRetirement|null $currentRetirement
 * @property-read TitleRetirement|null $previousRetirement
 * @property-read Collection<int, TitleRetirement> $retirements
 * @property-read Collection<int, TitleRetirement> $previousRetirements
 * @property-read TitleChampionship|null $currentChampionship
 * @property-read Collection<int, TitleChampionship> $championships
 * @property string $type
 * @property string|null $current_champion_type
 * @property int|null $current_champion_id
 * @property string|null $previous_champion_type
 * @property int|null $previous_champion_id
 * @property-read mixed $display_name
 * @method static \App\Builders\TitleBuilder<static>|Title activatedAfter(\Carbon\Carbon $date)
 * @method static \App\Builders\TitleBuilder<static>|Title activatedBefore(\Carbon\Carbon $date)
 * @method static \App\Builders\TitleBuilder<static>|Title active()
 * @method static \App\Builders\TitleBuilder<static>|Title activeDuring(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static \App\Builders\TitleBuilder<static>|Title competable()
 * @method static \App\Builders\TitleBuilder<static>|Title currentlyActive()
 * @method static \App\Builders\TitleBuilder<static>|Title currentlyInactive()
 * @method static \App\Builders\TitleBuilder<static>|Title deactivatedAfter(\Carbon\Carbon $date)
 * @method static \App\Builders\TitleBuilder<static>|Title inactive()
 * @method static \App\Builders\TitleBuilder<static>|Title neverActivated()
 * @method static \App\Builders\TitleBuilder<static>|Title newModelQuery()
 * @method static \App\Builders\TitleBuilder<static>|Title newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Title onlyTrashed()
 * @method static \App\Builders\TitleBuilder<static>|Title query()
 * @method static \App\Builders\TitleBuilder<static>|Title retired()
 * @method static \App\Builders\TitleBuilder<static>|Title withFutureActivation()
 * @method static \App\Builders\TitleBuilder<static>|Title withMultiplePeriods(int $minimumPeriods = 2)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Title withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Title withoutTrashed()
 */
	class Title extends \Eloquent implements \App\Models\Contracts\HasActivityPeriods, \App\Models\Contracts\HasDisplayName, \App\Models\Contracts\Retirable {}
}

namespace App\Models\Titles{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $title_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Title|null $title
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleActivityPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleActivityPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleActivityPeriod query()
 */
	class TitleActivityPeriod extends \Eloquent {}
}

namespace App\Models\Titles{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $title_id
 * @property int $event_match_id
 * @property int $champion_id
 * @property string $champion_type
 * @property int|null $won_event_match_id
 * @property int|null $lost_event_match_id
 * @property Carbon $won_at
 * @property Carbon|null $lost_at
 * @property-read EventMatch|null $wonEventMatch
 * @property-read EventMatch|null $lostEventMatch
 * @property-read Title|null $title
 * @property-read Wrestler|TagTeam $champion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship query()
 */
	class TitleChampionship extends \Eloquent {}
}

namespace App\Models\Titles{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $title_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Title|null $title
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleRetirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleRetirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleRetirement query()
 */
	class TitleRetirement extends \Eloquent {}
}

namespace App\Models\Users{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property UserStatus $status
 * @property string|null $avatar_path
 * @property string|null $phone_number
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role $role
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read Wrestler|null $wrestler
 * @method static \App\Builders\UserBuilder<static>|User newModelQuery()
 * @method static \App\Builders\UserBuilder<static>|User newQuery()
 * @method static \App\Builders\UserBuilder<static>|User query()
 */
	class User extends \Eloquent {}
}

namespace App\Models\Wrestlers{
/**
 *
 *
 * @mixin \Eloquent
 * @implements Bookable<EventMatchCompetitor>
 * @implements CanBeChampion<TitleChampionship>
 * @implements CanBeAStableMember<StableWrestler, static>
 * @implements CanBeATagTeamMember<TagTeamWrestler, static>
 * @implements Employable<WrestlerEmployment, static>
 * @implements Injurable<WrestlerInjury, static>
 * @implements Manageable<WrestlerManager, static>
 * @implements Retirable<WrestlerRetirement, static>
 * @implements Suspendable<WrestlerSuspension, static>
 * @property int $id
 * @property string $name
 * @property Height $height
 * @property int $weight
 * @property string $hometown
 * @property string|null $signature_move
 * @property EmploymentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read WrestlerEmployment|null $currentEmployment
 * @property-read WrestlerEmployment|null $firstEmployment
 * @property-read WrestlerEmployment|null $futureEmployment
 * @property-read WrestlerEmployment|null $previousEmployment
 * @property-read Collection<int, WrestlerEmployment> $employments
 * @property-read Collection<int, WrestlerEmployment> $previousEmployments
 * @property-read WrestlerInjury|null $currentInjury
 * @property-read WrestlerInjury|null $previousInjury
 * @property-read Collection<int, WrestlerInjury> $injuries
 * @property-read Collection<int, WrestlerInjury> $previousInjuries
 * @property-read WrestlerRetirement|null $currentRetirement
 * @property-read WrestlerRetirement|null $previousRetirement
 * @property-read Collection<int, WrestlerRetirement> $retirements
 * @property-read Collection<int, WrestlerRetirement> $previousRetirements
 * @property-read WrestlerSuspension|null $currentSuspension
 * @property-read WrestlerSuspension|null $previousSuspension
 * @property-read Collection<int, WrestlerSuspension> $suspensions
 * @property-read Collection<int, WrestlerSuspension> $previousSuspensions
 * @property-read Stable|null $currentStable
 * @property-read Collection<int, Manager> $managers
 * @property-read Collection<int, Manager> $currentManagers
 * @property-read Collection<int, Manager> $previousManagers
 * @property-read Collection<int, TagTeam> $tagTeams
 * @property-read Collection<int, TagTeam> $previousTagTeams
 * @property-read Collection<int, Stable> $stables
 * @property-read Collection<int, Stable> $previousStables
 * @property-read Collection<int, EventMatch> $matches
 * @property-read Collection<int, EventMatch> $previousMatches
 * @property-read TitleChampionship|null $currentChampionship
 * @property-read Collection<int, TitleChampionship> $titleChampionships
 * @property-read Collection<int, TitleChampionship> $currentChampionships
 * @property-read Collection<int, TitleChampionship> $previousTitleChampionships
 * @method string getNameLabel()
 * @property int|null $user_id
 * @property-read \App\Models\TagTeams\TagTeam $currentTagTeam
 * @property-read mixed $display_name
 * @property-read \App\Models\TagTeams\TagTeam $previousTagTeam
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler bookable()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler employed()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler futureEmployed()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler injured()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler newModelQuery()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wrestler onlyTrashed()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler query()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler released()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler retired()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler suspended()
 * @method static \App\Builders\WrestlerBuilder<static>|Wrestler unemployed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wrestler withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wrestler withoutTrashed()
 */
	class Wrestler extends \Eloquent implements \App\Models\Contracts\Bookable, \App\Models\Contracts\CanBeAStableMember, \App\Models\Contracts\CanBeATagTeamMember, \App\Models\Contracts\CanBeChampion, \App\Models\Contracts\Employable, \App\Models\Contracts\HasDisplayName, \App\Models\Contracts\Injurable, \App\Models\Contracts\Manageable, \App\Models\Contracts\Retirable, \App\Models\Contracts\Suspendable {}
}

namespace App\Models\Wrestlers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $wrestler_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wrestler|null $wrestler
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerEmployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerEmployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerEmployment query()
 */
	class WrestlerEmployment extends \Eloquent {}
}

namespace App\Models\Wrestlers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $wrestler_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wrestler|null $wrestler
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerInjury newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerInjury newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerInjury query()
 */
	class WrestlerInjury extends \Eloquent {}
}

namespace App\Models\Wrestlers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $wrestler_id
 * @property int $manager_id
 * @property Carbon $hired_at
 * @property Carbon|null $fired_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Manager $manager
 * @property-read Wrestler $wrestler
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerManager newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerManager newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerManager query()
 */
	class WrestlerManager extends \Eloquent {}
}

namespace App\Models\Wrestlers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $wrestler_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wrestler|null $wrestler
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerRetirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerRetirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerRetirement query()
 */
	class WrestlerRetirement extends \Eloquent {}
}

namespace App\Models\Wrestlers{
/**
 *
 *
 * @mixin \Eloquent
 * @property int $id
 * @property int $wrestler_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Wrestler|null $wrestler
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WrestlerSuspension query()
 */
	class WrestlerSuspension extends \Eloquent {}
}
