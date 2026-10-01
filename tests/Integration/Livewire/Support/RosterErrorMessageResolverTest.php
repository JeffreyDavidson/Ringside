<?php

declare(strict_types=1);

use App\Enums\BusinessRuleReason;
use App\Enums\Roster\RosterEntityType;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Exceptions\Roster\TagTeams\CannotBeEmployedException as TagTeamCannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException as TagTeamCannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException as TagTeamCannotBeReleasedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException as TagTeamCannotBeRestoredException;
use App\Exceptions\Roster\TagTeams\CannotBeRetiredException as TagTeamCannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException as TagTeamCannotBeSuspendedException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException as TagTeamCannotBeUnretiredException;
use App\Livewire\Support\RosterErrorMessageResolver;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Lang;

// Frozen rendered lifecycle messages: exception class x roster type x every BusinessRuleReason.
// Tag team reinstatement with the Injured reason is omitted; it is unreachable and used to render a raw key.
dataset('rendered lifecycle error messages', [
    'Wrestler employed' => [
        CannotBeEmployedException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'This wrestler is already hired.',
            'already_injured' => 'Unable to hire this wrestler at this time.',
            'already_retired' => 'Unable to hire this wrestler at this time.',
            'already_suspended' => 'Unable to hire this wrestler at this time.',
            'current_champion_missing' => 'Unable to hire this wrestler at this time.',
            'general' => 'Unable to hire this wrestler at this time.',
            'injured' => 'Unable to hire this wrestler at this time.',
            'not_deleted' => 'Unable to hire this wrestler at this time.',
            'not_injured' => 'Unable to hire this wrestler at this time.',
            'not_retired' => 'Unable to hire this wrestler at this time.',
            'not_suspended' => 'Unable to hire this wrestler at this time.',
            'retired' => 'Retired wrestlers cannot be hired.',
            'suspended' => 'Cannot hire suspended wrestlers.',
            'unemployed' => 'Unable to hire this wrestler at this time.',
        ],
    ],
    'Manager employed' => [
        CannotBeEmployedException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'This manager is already hired.',
            'already_injured' => 'Unable to hire this manager at this time.',
            'already_retired' => 'Unable to hire this manager at this time.',
            'already_suspended' => 'Unable to hire this manager at this time.',
            'current_champion_missing' => 'Unable to hire this manager at this time.',
            'general' => 'Unable to hire this manager at this time.',
            'injured' => 'Injured managers cannot be hired until they are cleared from injury.',
            'not_deleted' => 'Unable to hire this manager at this time.',
            'not_injured' => 'Unable to hire this manager at this time.',
            'not_retired' => 'Unable to hire this manager at this time.',
            'not_suspended' => 'Unable to hire this manager at this time.',
            'retired' => 'Retired managers cannot be hired without unretiring first.',
            'suspended' => 'Cannot hire suspended managers.',
            'unemployed' => 'Unable to hire this manager at this time.',
        ],
    ],
    'Referee employed' => [
        CannotBeEmployedException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'This referee is already hired.',
            'already_injured' => 'Unable to hire this referee at this time.',
            'already_retired' => 'Unable to hire this referee at this time.',
            'already_suspended' => 'Unable to hire this referee at this time.',
            'current_champion_missing' => 'Unable to hire this referee at this time.',
            'general' => 'Unable to hire this referee at this time.',
            'injured' => 'Unable to hire this referee at this time.',
            'not_deleted' => 'Unable to hire this referee at this time.',
            'not_injured' => 'Unable to hire this referee at this time.',
            'not_retired' => 'Unable to hire this referee at this time.',
            'not_suspended' => 'Unable to hire this referee at this time.',
            'retired' => 'Retired referees cannot be hired.',
            'suspended' => 'Cannot hire suspended referees.',
            'unemployed' => 'Unable to hire this referee at this time.',
        ],
    ],
    'Wrestler released' => [
        CannotBeReleasedException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'Unable to release this wrestler.',
            'already_injured' => 'Unable to release this wrestler.',
            'already_retired' => 'Unable to release this wrestler.',
            'already_suspended' => 'Unable to release this wrestler.',
            'current_champion_missing' => 'Unable to release this wrestler.',
            'general' => 'Unable to release this wrestler.',
            'injured' => 'Unable to release this wrestler.',
            'not_deleted' => 'Unable to release this wrestler.',
            'not_injured' => 'Unable to release this wrestler.',
            'not_retired' => 'Unable to release this wrestler.',
            'not_suspended' => 'Unable to release this wrestler.',
            'retired' => 'Unable to release this wrestler.',
            'suspended' => 'Unable to release this wrestler.',
            'unemployed' => 'This wrestler is not currently employed.',
        ],
    ],
    'Manager released' => [
        CannotBeReleasedException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'Unable to release this manager.',
            'already_injured' => 'Unable to release this manager.',
            'already_retired' => 'Unable to release this manager.',
            'already_suspended' => 'Unable to release this manager.',
            'current_champion_missing' => 'Unable to release this manager.',
            'general' => 'Unable to release this manager.',
            'injured' => 'Unable to release this manager.',
            'not_deleted' => 'Unable to release this manager.',
            'not_injured' => 'Unable to release this manager.',
            'not_retired' => 'Unable to release this manager.',
            'not_suspended' => 'Unable to release this manager.',
            'retired' => 'Unable to release this manager.',
            'suspended' => 'Manager must be reinstated before being released.',
            'unemployed' => 'This manager is not currently employed.',
        ],
    ],
    'Referee released' => [
        CannotBeReleasedException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'Unable to release this referee.',
            'already_injured' => 'Unable to release this referee.',
            'already_retired' => 'Unable to release this referee.',
            'already_suspended' => 'Unable to release this referee.',
            'current_champion_missing' => 'Unable to release this referee.',
            'general' => 'Unable to release this referee.',
            'injured' => 'Unable to release this referee.',
            'not_deleted' => 'Unable to release this referee.',
            'not_injured' => 'Unable to release this referee.',
            'not_retired' => 'Unable to release this referee.',
            'not_suspended' => 'Unable to release this referee.',
            'retired' => 'Unable to release this referee.',
            'suspended' => 'Unable to release this referee.',
            'unemployed' => 'This referee is not currently employed.',
        ],
    ],
    'Wrestler retired' => [
        CannotBeRetiredException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'Unable to retire this wrestler.',
            'already_injured' => 'Unable to retire this wrestler.',
            'already_retired' => 'This wrestler is already retired.',
            'already_suspended' => 'Unable to retire this wrestler.',
            'current_champion_missing' => 'Unable to retire this wrestler.',
            'general' => 'Unable to retire this wrestler.',
            'injured' => 'Unable to retire this wrestler.',
            'not_deleted' => 'Unable to retire this wrestler.',
            'not_injured' => 'Unable to retire this wrestler.',
            'not_retired' => 'Unable to retire this wrestler.',
            'not_suspended' => 'Unable to retire this wrestler.',
            'retired' => 'Unable to retire this wrestler.',
            'suspended' => 'Unable to retire this wrestler.',
            'unemployed' => 'Only employed wrestlers can retire.',
        ],
    ],
    'Manager retired' => [
        CannotBeRetiredException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'Unable to retire this manager.',
            'already_injured' => 'Unable to retire this manager.',
            'already_retired' => 'This manager is already retired.',
            'already_suspended' => 'Unable to retire this manager.',
            'current_champion_missing' => 'Unable to retire this manager.',
            'general' => 'Unable to retire this manager.',
            'injured' => 'Unable to retire this manager.',
            'not_deleted' => 'Unable to retire this manager.',
            'not_injured' => 'Unable to retire this manager.',
            'not_retired' => 'Unable to retire this manager.',
            'not_suspended' => 'Unable to retire this manager.',
            'retired' => 'Unable to retire this manager.',
            'suspended' => 'Suspended managers must be reinstated before retiring.',
            'unemployed' => 'Only employed managers can retire.',
        ],
    ],
    'Referee retired' => [
        CannotBeRetiredException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'Unable to retire this referee.',
            'already_injured' => 'Unable to retire this referee.',
            'already_retired' => 'This referee is already retired.',
            'already_suspended' => 'Unable to retire this referee.',
            'current_champion_missing' => 'Unable to retire this referee.',
            'general' => 'Unable to retire this referee.',
            'injured' => 'Unable to retire this referee.',
            'not_deleted' => 'Unable to retire this referee.',
            'not_injured' => 'Unable to retire this referee.',
            'not_retired' => 'Unable to retire this referee.',
            'not_suspended' => 'Unable to retire this referee.',
            'retired' => 'Unable to retire this referee.',
            'suspended' => 'Unable to retire this referee.',
            'unemployed' => 'Only employed referees can retire.',
        ],
    ],
    'Wrestler unretired' => [
        CannotBeUnretiredException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'Unable to bring this wrestler out of retirement.',
            'already_injured' => 'Unable to bring this wrestler out of retirement.',
            'already_retired' => 'Unable to bring this wrestler out of retirement.',
            'already_suspended' => 'Unable to bring this wrestler out of retirement.',
            'current_champion_missing' => 'Unable to bring this wrestler out of retirement.',
            'general' => 'Unable to bring this wrestler out of retirement.',
            'injured' => 'Unable to bring this wrestler out of retirement.',
            'not_deleted' => 'Unable to bring this wrestler out of retirement.',
            'not_injured' => 'Unable to bring this wrestler out of retirement.',
            'not_retired' => 'This wrestler is not currently retired.',
            'not_suspended' => 'Unable to bring this wrestler out of retirement.',
            'retired' => 'Unable to bring this wrestler out of retirement.',
            'suspended' => 'Unable to bring this wrestler out of retirement.',
            'unemployed' => 'Unable to bring this wrestler out of retirement.',
        ],
    ],
    'Manager unretired' => [
        CannotBeUnretiredException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'Unable to bring this manager out of retirement.',
            'already_injured' => 'Unable to bring this manager out of retirement.',
            'already_retired' => 'Unable to bring this manager out of retirement.',
            'already_suspended' => 'Unable to bring this manager out of retirement.',
            'current_champion_missing' => 'Unable to bring this manager out of retirement.',
            'general' => 'Unable to bring this manager out of retirement.',
            'injured' => 'Unable to bring this manager out of retirement.',
            'not_deleted' => 'Unable to bring this manager out of retirement.',
            'not_injured' => 'Unable to bring this manager out of retirement.',
            'not_retired' => 'This manager is not currently retired.',
            'not_suspended' => 'Unable to bring this manager out of retirement.',
            'retired' => 'Unable to bring this manager out of retirement.',
            'suspended' => 'Unable to bring this manager out of retirement.',
            'unemployed' => 'Unable to bring this manager out of retirement.',
        ],
    ],
    'Referee unretired' => [
        CannotBeUnretiredException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'Unable to bring this referee out of retirement.',
            'already_injured' => 'Unable to bring this referee out of retirement.',
            'already_retired' => 'Unable to bring this referee out of retirement.',
            'already_suspended' => 'Unable to bring this referee out of retirement.',
            'current_champion_missing' => 'Unable to bring this referee out of retirement.',
            'general' => 'Unable to bring this referee out of retirement.',
            'injured' => 'Unable to bring this referee out of retirement.',
            'not_deleted' => 'Unable to bring this referee out of retirement.',
            'not_injured' => 'Unable to bring this referee out of retirement.',
            'not_retired' => 'This referee is not currently retired.',
            'not_suspended' => 'Unable to bring this referee out of retirement.',
            'retired' => 'Unable to bring this referee out of retirement.',
            'suspended' => 'Unable to bring this referee out of retirement.',
            'unemployed' => 'Unable to bring this referee out of retirement.',
        ],
    ],
    'Wrestler suspended' => [
        CannotBeSuspendedException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'Unable to suspend this wrestler.',
            'already_injured' => 'Unable to suspend this wrestler.',
            'already_retired' => 'Unable to suspend this wrestler.',
            'already_suspended' => 'This wrestler is already suspended.',
            'current_champion_missing' => 'Unable to suspend this wrestler.',
            'general' => 'Unable to suspend this wrestler.',
            'injured' => 'Unable to suspend this wrestler.',
            'not_deleted' => 'Unable to suspend this wrestler.',
            'not_injured' => 'Unable to suspend this wrestler.',
            'not_retired' => 'Unable to suspend this wrestler.',
            'not_suspended' => 'Unable to suspend this wrestler.',
            'retired' => 'Unable to suspend this wrestler.',
            'suspended' => 'Unable to suspend this wrestler.',
            'unemployed' => 'Unable to suspend this wrestler.',
        ],
    ],
    'Manager suspended' => [
        CannotBeSuspendedException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'Unable to suspend this manager.',
            'already_injured' => 'Unable to suspend this manager.',
            'already_retired' => 'Unable to suspend this manager.',
            'already_suspended' => 'This manager is already suspended.',
            'current_champion_missing' => 'Unable to suspend this manager.',
            'general' => 'Unable to suspend this manager.',
            'injured' => 'Injured managers cannot be suspended.',
            'not_deleted' => 'Unable to suspend this manager.',
            'not_injured' => 'Unable to suspend this manager.',
            'not_retired' => 'Unable to suspend this manager.',
            'not_suspended' => 'Unable to suspend this manager.',
            'retired' => 'Unable to suspend this manager.',
            'suspended' => 'Unable to suspend this manager.',
            'unemployed' => 'Only employed managers can be suspended.',
        ],
    ],
    'Referee suspended' => [
        CannotBeSuspendedException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'Unable to suspend this referee.',
            'already_injured' => 'Unable to suspend this referee.',
            'already_retired' => 'Unable to suspend this referee.',
            'already_suspended' => 'This referee is already suspended.',
            'current_champion_missing' => 'Unable to suspend this referee.',
            'general' => 'Unable to suspend this referee.',
            'injured' => 'Unable to suspend this referee.',
            'not_deleted' => 'Unable to suspend this referee.',
            'not_injured' => 'Unable to suspend this referee.',
            'not_retired' => 'Unable to suspend this referee.',
            'not_suspended' => 'Unable to suspend this referee.',
            'retired' => 'Unable to suspend this referee.',
            'suspended' => 'Unable to suspend this referee.',
            'unemployed' => 'Only employed referees can be suspended.',
        ],
    ],
    'Wrestler reinstated' => [
        CannotBeReinstatedException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'Unable to reinstate this wrestler.',
            'already_injured' => 'Unable to reinstate this wrestler.',
            'already_retired' => 'Unable to reinstate this wrestler.',
            'already_suspended' => 'Unable to reinstate this wrestler.',
            'current_champion_missing' => 'Unable to reinstate this wrestler.',
            'general' => 'Unable to reinstate this wrestler.',
            'injured' => 'Injured wrestlers must be cleared from injury instead of reinstated.',
            'not_deleted' => 'Unable to reinstate this wrestler.',
            'not_injured' => 'Unable to reinstate this wrestler.',
            'not_retired' => 'Unable to reinstate this wrestler.',
            'not_suspended' => 'This wrestler is not currently suspended.',
            'retired' => 'Unable to reinstate this wrestler.',
            'suspended' => 'Unable to reinstate this wrestler.',
            'unemployed' => 'Unable to reinstate this wrestler.',
        ],
    ],
    'Manager reinstated' => [
        CannotBeReinstatedException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'Unable to reinstate this manager.',
            'already_injured' => 'Unable to reinstate this manager.',
            'already_retired' => 'Unable to reinstate this manager.',
            'already_suspended' => 'Unable to reinstate this manager.',
            'current_champion_missing' => 'Unable to reinstate this manager.',
            'general' => 'Unable to reinstate this manager.',
            'injured' => 'Managers cannot be reinstated while injured.',
            'not_deleted' => 'Unable to reinstate this manager.',
            'not_injured' => 'Unable to reinstate this manager.',
            'not_retired' => 'Unable to reinstate this manager.',
            'not_suspended' => 'This manager is not currently suspended.',
            'retired' => 'Unable to reinstate this manager.',
            'suspended' => 'Unable to reinstate this manager.',
            'unemployed' => 'Unable to reinstate this manager.',
        ],
    ],
    'Referee reinstated' => [
        CannotBeReinstatedException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'Unable to reinstate this referee.',
            'already_injured' => 'Unable to reinstate this referee.',
            'already_retired' => 'Unable to reinstate this referee.',
            'already_suspended' => 'Unable to reinstate this referee.',
            'current_champion_missing' => 'Unable to reinstate this referee.',
            'general' => 'Unable to reinstate this referee.',
            'injured' => 'Injured referees must be cleared from injury instead of reinstated.',
            'not_deleted' => 'Unable to reinstate this referee.',
            'not_injured' => 'Unable to reinstate this referee.',
            'not_retired' => 'Unable to reinstate this referee.',
            'not_suspended' => 'This referee is not currently suspended.',
            'retired' => 'Unable to reinstate this referee.',
            'suspended' => 'Unable to reinstate this referee.',
            'unemployed' => 'Unable to reinstate this referee.',
        ],
    ],
    'Wrestler injured' => [
        CannotBeInjuredException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'Unable to record injury for this wrestler.',
            'already_injured' => 'This wrestler is already injured.',
            'already_retired' => 'Unable to record injury for this wrestler.',
            'already_suspended' => 'Unable to record injury for this wrestler.',
            'current_champion_missing' => 'Unable to record injury for this wrestler.',
            'general' => 'Unable to record injury for this wrestler.',
            'injured' => 'Unable to record injury for this wrestler.',
            'not_deleted' => 'Unable to record injury for this wrestler.',
            'not_injured' => 'Unable to record injury for this wrestler.',
            'not_retired' => 'Unable to record injury for this wrestler.',
            'not_suspended' => 'Unable to record injury for this wrestler.',
            'retired' => 'Unable to record injury for this wrestler.',
            'suspended' => 'Unable to record injury for this wrestler.',
            'unemployed' => 'Unable to record injury for this wrestler.',
        ],
    ],
    'Manager injured' => [
        CannotBeInjuredException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'Unable to record injury for this manager.',
            'already_injured' => 'This manager is already injured.',
            'already_retired' => 'Unable to record injury for this manager.',
            'already_suspended' => 'Unable to record injury for this manager.',
            'current_champion_missing' => 'Unable to record injury for this manager.',
            'general' => 'Unable to record injury for this manager.',
            'injured' => 'Unable to record injury for this manager.',
            'not_deleted' => 'Unable to record injury for this manager.',
            'not_injured' => 'Unable to record injury for this manager.',
            'not_retired' => 'Unable to record injury for this manager.',
            'not_suspended' => 'Unable to record injury for this manager.',
            'retired' => 'Unable to record injury for this manager.',
            'suspended' => 'Suspended managers cannot be injured.',
            'unemployed' => 'Only employed managers can be injured.',
        ],
    ],
    'Referee injured' => [
        CannotBeInjuredException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'Unable to record injury for this referee.',
            'already_injured' => 'This referee is already injured.',
            'already_retired' => 'Unable to record injury for this referee.',
            'already_suspended' => 'Unable to record injury for this referee.',
            'current_champion_missing' => 'Unable to record injury for this referee.',
            'general' => 'Unable to record injury for this referee.',
            'injured' => 'Unable to record injury for this referee.',
            'not_deleted' => 'Unable to record injury for this referee.',
            'not_injured' => 'Unable to record injury for this referee.',
            'not_retired' => 'Unable to record injury for this referee.',
            'not_suspended' => 'Unable to record injury for this referee.',
            'retired' => 'Unable to record injury for this referee.',
            'suspended' => 'Unable to record injury for this referee.',
            'unemployed' => 'Only employed referees can be injured.',
        ],
    ],
    'Wrestler clearedfrominjury' => [
        CannotBeClearedFromInjuryException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'Unable to clear this wrestler from injury.',
            'already_injured' => 'Unable to clear this wrestler from injury.',
            'already_retired' => 'Unable to clear this wrestler from injury.',
            'already_suspended' => 'Unable to clear this wrestler from injury.',
            'current_champion_missing' => 'Unable to clear this wrestler from injury.',
            'general' => 'Unable to clear this wrestler from injury.',
            'injured' => 'Unable to clear this wrestler from injury.',
            'not_deleted' => 'Unable to clear this wrestler from injury.',
            'not_injured' => 'This wrestler is not currently injured.',
            'not_retired' => 'Unable to clear this wrestler from injury.',
            'not_suspended' => 'Unable to clear this wrestler from injury.',
            'retired' => 'Unable to clear this wrestler from injury.',
            'suspended' => 'Unable to clear this wrestler from injury.',
            'unemployed' => 'Unable to clear this wrestler from injury.',
        ],
    ],
    'Manager clearedfrominjury' => [
        CannotBeClearedFromInjuryException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'Unable to clear this manager from injury.',
            'already_injured' => 'Unable to clear this manager from injury.',
            'already_retired' => 'Unable to clear this manager from injury.',
            'already_suspended' => 'Unable to clear this manager from injury.',
            'current_champion_missing' => 'Unable to clear this manager from injury.',
            'general' => 'Unable to clear this manager from injury.',
            'injured' => 'Unable to clear this manager from injury.',
            'not_deleted' => 'Unable to clear this manager from injury.',
            'not_injured' => 'This manager is not currently injured.',
            'not_retired' => 'Unable to clear this manager from injury.',
            'not_suspended' => 'Unable to clear this manager from injury.',
            'retired' => 'Unable to clear this manager from injury.',
            'suspended' => 'Unable to clear this manager from injury.',
            'unemployed' => 'Unable to clear this manager from injury.',
        ],
    ],
    'Referee clearedfrominjury' => [
        CannotBeClearedFromInjuryException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'Unable to clear this referee from injury.',
            'already_injured' => 'Unable to clear this referee from injury.',
            'already_retired' => 'Unable to clear this referee from injury.',
            'already_suspended' => 'Unable to clear this referee from injury.',
            'current_champion_missing' => 'Unable to clear this referee from injury.',
            'general' => 'Unable to clear this referee from injury.',
            'injured' => 'Unable to clear this referee from injury.',
            'not_deleted' => 'Unable to clear this referee from injury.',
            'not_injured' => 'This referee is not currently injured.',
            'not_retired' => 'Unable to clear this referee from injury.',
            'not_suspended' => 'Unable to clear this referee from injury.',
            'retired' => 'Unable to clear this referee from injury.',
            'suspended' => 'Unable to clear this referee from injury.',
            'unemployed' => 'Unable to clear this referee from injury.',
        ],
    ],
    'Wrestler restored' => [
        CannotBeRestoredException::class,
        RosterEntityType::Wrestler,
        [
            'already_employed' => 'Unable to restore this wrestler.',
            'already_injured' => 'Unable to restore this wrestler.',
            'already_retired' => 'Unable to restore this wrestler.',
            'already_suspended' => 'Unable to restore this wrestler.',
            'current_champion_missing' => 'Unable to restore this wrestler.',
            'general' => 'Unable to restore this wrestler.',
            'injured' => 'Unable to restore this wrestler.',
            'not_deleted' => 'This wrestler has not been deleted.',
            'not_injured' => 'Unable to restore this wrestler.',
            'not_retired' => 'Unable to restore this wrestler.',
            'not_suspended' => 'Unable to restore this wrestler.',
            'retired' => 'Unable to restore this wrestler.',
            'suspended' => 'Unable to restore this wrestler.',
            'unemployed' => 'Unable to restore this wrestler.',
        ],
    ],
    'Manager restored' => [
        CannotBeRestoredException::class,
        RosterEntityType::Manager,
        [
            'already_employed' => 'Unable to restore this manager.',
            'already_injured' => 'Unable to restore this manager.',
            'already_retired' => 'Unable to restore this manager.',
            'already_suspended' => 'Unable to restore this manager.',
            'current_champion_missing' => 'Unable to restore this manager.',
            'general' => 'Unable to restore this manager.',
            'injured' => 'Unable to restore this manager.',
            'not_deleted' => 'This manager has not been deleted.',
            'not_injured' => 'Unable to restore this manager.',
            'not_retired' => 'Unable to restore this manager.',
            'not_suspended' => 'Unable to restore this manager.',
            'retired' => 'Unable to restore this manager.',
            'suspended' => 'Unable to restore this manager.',
            'unemployed' => 'Unable to restore this manager.',
        ],
    ],
    'Referee restored' => [
        CannotBeRestoredException::class,
        RosterEntityType::Referee,
        [
            'already_employed' => 'Unable to restore this referee.',
            'already_injured' => 'Unable to restore this referee.',
            'already_retired' => 'Unable to restore this referee.',
            'already_suspended' => 'Unable to restore this referee.',
            'current_champion_missing' => 'Unable to restore this referee.',
            'general' => 'Unable to restore this referee.',
            'injured' => 'Unable to restore this referee.',
            'not_deleted' => 'This referee has not been deleted.',
            'not_injured' => 'Unable to restore this referee.',
            'not_retired' => 'Unable to restore this referee.',
            'not_suspended' => 'Unable to restore this referee.',
            'retired' => 'Unable to restore this referee.',
            'suspended' => 'Unable to restore this referee.',
            'unemployed' => 'Unable to restore this referee.',
        ],
    ],
    'TagTeam employed' => [
        TagTeamCannotBeEmployedException::class,
        RosterEntityType::TagTeam,
        [
            'already_employed' => 'This tag team is already hired.',
            'already_injured' => 'Unable to hire this tag team at this time.',
            'already_retired' => 'Unable to hire this tag team at this time.',
            'already_suspended' => 'Unable to hire this tag team at this time.',
            'current_champion_missing' => 'Unable to hire this tag team at this time.',
            'general' => 'Unable to hire this tag team at this time.',
            'injured' => 'Unable to hire this tag team at this time.',
            'not_deleted' => 'Unable to hire this tag team at this time.',
            'not_injured' => 'Unable to hire this tag team at this time.',
            'not_retired' => 'Unable to hire this tag team at this time.',
            'not_suspended' => 'Unable to hire this tag team at this time.',
            'retired' => 'Retired tag teams cannot be hired without unretiring first.',
            'suspended' => 'Cannot hire suspended tag teams.',
            'unemployed' => 'Unable to hire this tag team at this time.',
        ],
    ],
    'TagTeam released' => [
        TagTeamCannotBeReleasedException::class,
        RosterEntityType::TagTeam,
        [
            'already_employed' => 'Unable to release this tag team.',
            'already_injured' => 'Unable to release this tag team.',
            'already_retired' => 'Unable to release this tag team.',
            'already_suspended' => 'Unable to release this tag team.',
            'current_champion_missing' => 'Unable to release this tag team.',
            'general' => 'Unable to release this tag team.',
            'injured' => 'Unable to release this tag team.',
            'not_deleted' => 'Unable to release this tag team.',
            'not_injured' => 'Unable to release this tag team.',
            'not_retired' => 'Unable to release this tag team.',
            'not_suspended' => 'Unable to release this tag team.',
            'retired' => 'Unable to release this tag team.',
            'suspended' => 'Tag team must be reinstated before being released.',
            'unemployed' => 'This tag team is not currently employed.',
        ],
    ],
    'TagTeam retired' => [
        TagTeamCannotBeRetiredException::class,
        RosterEntityType::TagTeam,
        [
            'already_employed' => 'Unable to retire this tag team.',
            'already_injured' => 'Unable to retire this tag team.',
            'already_retired' => 'This tag team is already retired.',
            'already_suspended' => 'Unable to retire this tag team.',
            'current_champion_missing' => 'Unable to retire this tag team.',
            'general' => 'Unable to retire this tag team.',
            'injured' => 'Unable to retire this tag team.',
            'not_deleted' => 'Unable to retire this tag team.',
            'not_injured' => 'Unable to retire this tag team.',
            'not_retired' => 'Unable to retire this tag team.',
            'not_suspended' => 'Unable to retire this tag team.',
            'retired' => 'Unable to retire this tag team.',
            'suspended' => 'Suspended tag teams must be reinstated before retiring.',
            'unemployed' => 'Only employed tag teams can retire.',
        ],
    ],
    'TagTeam unretired' => [
        TagTeamCannotBeUnretiredException::class,
        RosterEntityType::TagTeam,
        [
            'already_employed' => 'Unable to bring this tag team out of retirement.',
            'already_injured' => 'Unable to bring this tag team out of retirement.',
            'already_retired' => 'Unable to bring this tag team out of retirement.',
            'already_suspended' => 'Unable to bring this tag team out of retirement.',
            'current_champion_missing' => 'Unable to bring this tag team out of retirement.',
            'general' => 'Unable to bring this tag team out of retirement.',
            'injured' => 'Unable to bring this tag team out of retirement.',
            'not_deleted' => 'Unable to bring this tag team out of retirement.',
            'not_injured' => 'Unable to bring this tag team out of retirement.',
            'not_retired' => 'This tag team is not currently retired.',
            'not_suspended' => 'Unable to bring this tag team out of retirement.',
            'retired' => 'Unable to bring this tag team out of retirement.',
            'suspended' => 'Unable to bring this tag team out of retirement.',
            'unemployed' => 'Unable to bring this tag team out of retirement.',
        ],
    ],
    'TagTeam suspended' => [
        TagTeamCannotBeSuspendedException::class,
        RosterEntityType::TagTeam,
        [
            'already_employed' => 'Unable to suspend this tag team.',
            'already_injured' => 'Unable to suspend this tag team.',
            'already_retired' => 'Unable to suspend this tag team.',
            'already_suspended' => 'This tag team is already suspended.',
            'current_champion_missing' => 'Unable to suspend this tag team.',
            'general' => 'Unable to suspend this tag team.',
            'injured' => 'Unable to suspend this tag team.',
            'not_deleted' => 'Unable to suspend this tag team.',
            'not_injured' => 'Unable to suspend this tag team.',
            'not_retired' => 'Unable to suspend this tag team.',
            'not_suspended' => 'Unable to suspend this tag team.',
            'retired' => 'Unable to suspend this tag team.',
            'suspended' => 'Unable to suspend this tag team.',
            'unemployed' => 'Only employed tag teams can be suspended.',
        ],
    ],
    'TagTeam reinstated' => [
        TagTeamCannotBeReinstatedException::class,
        RosterEntityType::TagTeam,
        [
            'already_employed' => 'Unable to reinstate this tag team.',
            'already_injured' => 'Unable to reinstate this tag team.',
            'already_retired' => 'Unable to reinstate this tag team.',
            'already_suspended' => 'Unable to reinstate this tag team.',
            'current_champion_missing' => 'Unable to reinstate this tag team.',
            'general' => 'Unable to reinstate this tag team.',
            'not_deleted' => 'Unable to reinstate this tag team.',
            'not_injured' => 'Unable to reinstate this tag team.',
            'not_retired' => 'Unable to reinstate this tag team.',
            'not_suspended' => 'This tag team is not currently suspended.',
            'retired' => 'Unable to reinstate this tag team.',
            'suspended' => 'Unable to reinstate this tag team.',
            'unemployed' => 'Unable to reinstate this tag team.',
        ],
    ],
    'TagTeam restored' => [
        TagTeamCannotBeRestoredException::class,
        RosterEntityType::TagTeam,
        [
            'already_employed' => 'Unable to restore this tag team.',
            'already_injured' => 'Unable to restore this tag team.',
            'already_retired' => 'Unable to restore this tag team.',
            'already_suspended' => 'Unable to restore this tag team.',
            'current_champion_missing' => 'Unable to restore this tag team.',
            'general' => 'Unable to restore this tag team.',
            'injured' => 'Unable to restore this tag team.',
            'not_deleted' => 'This tag team has not been deleted.',
            'not_injured' => 'Unable to restore this tag team.',
            'not_retired' => 'Unable to restore this tag team.',
            'not_suspended' => 'Unable to restore this tag team.',
            'retired' => 'Unable to restore this tag team.',
            'suspended' => 'Unable to restore this tag team.',
            'unemployed' => 'Unable to restore this tag team.',
        ],
    ],
]);

describe('roster error messages', function (): void {
    test('it renders the same lifecycle message for every exception, reason, and roster type', function (string $exceptionClass, RosterEntityType $entityType, array $expectedMessages): void {
        // Arrange
        if (! is_subclass_of($exceptionClass, BaseBusinessException::class)) {
            throw new LogicException("{$exceptionClass} is not a business exception.");
        }

        $renderedMessages = [];

        // Act
        foreach (array_keys($expectedMessages) as $reason) {
            $exception = new $exceptionClass('Test failure', reason: BusinessRuleReason::from($reason));
            $renderedMessages[$reason] = __(RosterErrorMessageResolver::translationKey($exception, $entityType));
        }

        // Assert
        expect($renderedMessages)->toBe($expectedMessages);
    })->with('rendered lifecycle error messages');

    test('it renders the entity general message for an unmapped business exception', function (RosterEntityType $entityType, string $expectedMessage): void {
        // Arrange
        $exception = CannotBeDeletedException::alreadyDeleted(new Wrestler(['name' => 'Test Wrestler']));

        // Act
        $message = __(RosterErrorMessageResolver::translationKey($exception, $entityType));

        // Assert
        expect($message)->toBe($expectedMessage);
    })->with([
        'wrestler' => [RosterEntityType::Wrestler, 'An unexpected error occurred. Please try again.'],
        'manager' => [RosterEntityType::Manager, 'An unexpected error occurred with this manager action. Please try again.'],
        'referee' => [RosterEntityType::Referee, 'An unexpected error occurred. Please try again.'],
        'tag team' => [RosterEntityType::TagTeam, 'An unexpected error occurred with this tag team action. Please try again.'],
    ]);

    test('it maps roster failures from stable reasons instead of message text', function (): void {
        // Arrange
        $wrestler = new Wrestler(['name' => 'Test Wrestler']);
        $exception = CannotBeReinstatedException::injured($wrestler, 'wording may change');

        // Act
        $wrestlerKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Wrestler);
        $managerKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Manager);
        $refereeKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Referee);

        // Assert
        expect($exception->reason())->toBe(BusinessRuleReason::Injured)
            ->and($wrestlerKey)
            ->toBe('wrestlers.errors.reinstate.injured')
            ->and($managerKey)
            ->toBe('managers.errors.reinstate.injured')
            ->and($refereeKey)
            ->toBe('referees.errors.reinstate.injured');
    });

    test('it maps common lifecycle reasons for each roster presentation', function (BaseBusinessException $exception, RosterEntityType $entityType, string $expectedKey): void {
        // Arrange: each dataset supplies an independent failure and presentation.

        // Act
        $translationKey = RosterErrorMessageResolver::translationKey($exception, $entityType);

        // Assert
        expect($translationKey)->toBe($expectedKey)
            ->and(Lang::has($translationKey, 'en', false))->toBeTrue();
    })->with([
        'wrestlers.errors.employ.already_employed' => [
            fn (): BaseBusinessException => CannotBeEmployedException::employed(new Wrestler(['name' => 'Test Wrestler'])),
            RosterEntityType::Wrestler,
            'wrestlers.errors.employ.already_employed',
        ],
        'managers.errors.suspend.unemployed' => [
            fn (): BaseBusinessException => CannotBeSuspendedException::unemployed(Manager::factory()->make()),
            RosterEntityType::Manager,
            'managers.errors.suspend.unemployed',
        ],
        'wrestlers.errors.suspend.default' => [
            fn (): BaseBusinessException => CannotBeSuspendedException::unemployed(Wrestler::factory()->make()),
            RosterEntityType::Wrestler,
            'wrestlers.errors.suspend.default',
        ],
        'referees.errors.suspend.unemployed' => [
            fn (): BaseBusinessException => CannotBeSuspendedException::unemployed(Referee::factory()->make()),
            RosterEntityType::Referee,
            'referees.errors.suspend.unemployed',
        ],
        'referees.errors.injure.unemployed' => [
            fn (): BaseBusinessException => CannotBeInjuredException::unemployed(Referee::factory()->make()),
            RosterEntityType::Referee,
            'referees.errors.injure.unemployed',
        ],
        'wrestlers.errors.injure.default' => [
            fn (): BaseBusinessException => CannotBeInjuredException::unemployed(Wrestler::factory()->make()),
            RosterEntityType::Wrestler,
            'wrestlers.errors.injure.default',
        ],
        'managers.errors.injure.unemployed' => [
            fn (): BaseBusinessException => CannotBeInjuredException::unemployed(Manager::factory()->make()),
            RosterEntityType::Manager,
            'managers.errors.injure.unemployed',
        ],
        'wrestlers.errors.clear_from_injury.not_injured' => [
            fn (): BaseBusinessException => CannotBeClearedFromInjuryException::notInjured(new Wrestler(['name' => 'Test Wrestler'])),
            RosterEntityType::Wrestler,
            'wrestlers.errors.clear_from_injury.not_injured',
        ],
    ]);

    test('it maps restoration failures from the not-deleted reason', function (): void {
        // Arrange
        $wrestler = new Wrestler(['name' => 'Test Wrestler']);
        $exception = CannotBeRestoredException::notDeleted($wrestler);

        // Act
        $restorationKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Wrestler);

        // Assert
        expect($exception->reason())->toBe(BusinessRuleReason::NotDeleted)
            ->and($restorationKey)
            ->toBe('wrestlers.errors.restore.not_deleted');
    });

    test('it maps available roster reinstatement failures to suspension guidance', function (): void {
        // Arrange
        $wrestler = new Wrestler(['name' => 'Test Wrestler']);
        $exception = CannotBeReinstatedException::available($wrestler);

        // Act
        $wrestlerKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Wrestler);
        $managerKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Manager);
        $refereeKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Referee);

        // Assert
        expect($exception->reason())->toBe(BusinessRuleReason::NotSuspended)
            ->and($wrestlerKey)
            ->toBe('wrestlers.errors.reinstate.not_suspended')
            ->and($managerKey)
            ->toBe('managers.errors.reinstate.not_suspended')
            ->and($refereeKey)
            ->toBe('referees.errors.reinstate.not_suspended');
    });

    test('it maps tag team reinstatement failures from a stable reason', function (): void {
        // Arrange
        $tagTeam = new TagTeam(['name' => 'Test Team']);
        $exception = TagTeamCannotBeReinstatedException::notSuspended($tagTeam);

        // Act
        $reinstatementKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::TagTeam);

        // Assert
        expect($exception->reason())->toBe(BusinessRuleReason::NotSuspended)
            ->and($reinstatementKey)
            ->toBe('tag-teams.errors.reinstate.not_suspended');
    });

    test('it maps tag team lifecycle failures from stable reasons', function (BaseBusinessException $exception, RosterEntityType $entityType, string $expectedKey): void {
        // Arrange: each dataset supplies an independent failure and presentation.

        // Act
        $translationKey = RosterErrorMessageResolver::translationKey($exception, $entityType);

        // Assert
        expect($translationKey)->toBe($expectedKey)
            ->and(Lang::has($translationKey, 'en', false))->toBeTrue();
    })->with([
        'tag-teams.errors.employ.already_employed' => [
            fn (): BaseBusinessException => TagTeamCannotBeEmployedException::alreadyEmployed(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.employ.already_employed',
        ],
        'tag-teams.errors.employ.retired' => [
            fn (): BaseBusinessException => TagTeamCannotBeEmployedException::retired(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.employ.retired',
        ],
        'tag-teams.errors.release.unemployed' => [
            fn (): BaseBusinessException => TagTeamCannotBeReleasedException::notEmployed(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.release.unemployed',
        ],
        'tag-teams.errors.retire.unemployed' => [
            fn (): BaseBusinessException => TagTeamCannotBeRetiredException::notEmployed(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.retire.unemployed',
        ],
        'tag-teams.errors.retire.already_retired' => [
            fn (): BaseBusinessException => TagTeamCannotBeRetiredException::alreadyRetired(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.retire.already_retired',
        ],
        'tag-teams.errors.unretire.not_retired' => [
            fn (): BaseBusinessException => TagTeamCannotBeUnretiredException::notRetired(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.unretire.not_retired',
        ],
        'tag-teams.errors.suspend.unemployed' => [
            fn (): BaseBusinessException => TagTeamCannotBeSuspendedException::notEmployed(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.suspend.unemployed',
        ],
        'tag-teams.errors.suspend.already_suspended' => [
            fn (): BaseBusinessException => TagTeamCannotBeSuspendedException::alreadySuspended(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.suspend.already_suspended',
        ],
        'tag-teams.errors.restore.not_deleted' => [
            fn (): BaseBusinessException => TagTeamCannotBeRestoredException::notDeleted(new TagTeam(['name' => 'Test TagTeam'])),
            RosterEntityType::TagTeam,
            'tag-teams.errors.restore.not_deleted',
        ],
    ]);

    test('it uses a general message for an unmapped business exception', function (): void {
        // Arrange
        $wrestler = new Wrestler(['name' => 'Test Wrestler']);
        $exception = CannotBeDeletedException::alreadyDeleted($wrestler);

        // Act
        $fallbackKey = RosterErrorMessageResolver::translationKey($exception, RosterEntityType::Wrestler);

        // Assert
        expect($fallbackKey)
            ->toBe('wrestlers.errors.general');
    });
});
