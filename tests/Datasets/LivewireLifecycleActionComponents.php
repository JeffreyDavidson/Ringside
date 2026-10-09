<?php

declare(strict_types=1);

use App\Actions\Managers\ClearFromInjuryAction as ClearManagerFromInjury;
use App\Actions\Managers\EmployAction as EmployManager;
use App\Actions\Managers\InjureAction as InjureManager;
use App\Actions\Managers\ReleaseAction as ReleaseManager;
use App\Actions\Managers\RetireAction as RetireManager;
use App\Actions\Managers\SuspendAction as SuspendManager;
use App\Actions\Managers\UnretireAction as UnretireManager;
use App\Actions\Referees\ClearFromInjuryAction as ClearRefereeFromInjury;
use App\Actions\Referees\EmployAction as EmployReferee;
use App\Actions\Referees\InjureAction as InjureReferee;
use App\Actions\Referees\ReinstateAction as ReinstateReferee;
use App\Actions\Referees\ReleaseAction as ReleaseReferee;
use App\Actions\Referees\RetireAction as RetireReferee;
use App\Actions\Referees\SuspendAction as SuspendReferee;
use App\Actions\Referees\UnretireAction as UnretireReferee;
use App\Actions\TagTeams\EmployAction as EmployTagTeam;
use App\Actions\TagTeams\ReinstateAction as ReinstateTagTeam;
use App\Actions\TagTeams\ReleaseAction as ReleaseTagTeam;
use App\Actions\TagTeams\RetireAction as RetireTagTeam;
use App\Actions\TagTeams\SuspendAction as SuspendTagTeam;
use App\Actions\TagTeams\UnretireAction as UnretireTagTeam;
use App\Actions\Titles\DebutAction as DebutTitle;
use App\Actions\Titles\PullAction as PullTitle;
use App\Actions\Titles\ReinstateAction as ReinstateTitle;
use App\Actions\Titles\RetireAction as RetireTitle;
use App\Actions\Titles\UnretireAction as UnretireTitle;
use App\Actions\Wrestlers\ClearFromInjuryAction as ClearWrestlerFromInjury;
use App\Actions\Wrestlers\EmployAction as EmployWrestler;
use App\Actions\Wrestlers\InjureAction as InjureWrestler;
use App\Actions\Wrestlers\ReinstateAction as ReinstateWrestler;
use App\Actions\Wrestlers\ReleaseAction as ReleaseWrestler;
use App\Actions\Wrestlers\RetireAction as RetireWrestler;
use App\Actions\Wrestlers\SuspendAction as SuspendWrestler;
use App\Actions\Wrestlers\UnretireAction as UnretireWrestler;
use App\Enums\Roster\RosterLifecycleAction;
use App\Enums\Stables\StableLifecycleAction;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Livewire\Managers\Components\Actions as ManagerActions;
use App\Livewire\Referees\Components\Actions as RefereeActions;
use App\Livewire\Stables\Components\Actions as StableActions;
use App\Livewire\TagTeams\Components\Actions as TagTeamActions;
use App\Livewire\Titles\Components\Actions as TitleActions;
use App\Livewire\Wrestlers\Components\Actions as WrestlerActions;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use JMac\Testing\Double;
use Livewire\Features\SupportTesting\Testable;

/*
 * The detail-page Actions components of every lifecycle-managed entity share
 * one contract: they mount a record, run the lifecycle action behind a button,
 * dispatch "<entity>-updated" plus flash feedback, show only the buttons that
 * fit the record's state and refuse users who lack the ability. This catalogue
 * holds what differs per entity; the datasets below slice it for each test.
 */

/**
 * Buttons of the wrestler, referee and manager (individual) components per state.
 *
 * @return array<string, array{string, list<string>, list<string>}>
 */
function livewireIndividualButtonStates(): array
{
    return [
        'unemployed' => ['unemployed', ['employ'], ['release', 'suspend', 'reinstate', 'injure', 'clearFromInjury', 'retire', 'unretire']],
        'employed' => ['employed', ['release', 'suspend', 'injure', 'retire'], ['employ', 'reinstate', 'clearFromInjury', 'unretire']],
        'suspended' => ['suspended', ['release', 'reinstate', 'retire'], ['employ', 'suspend', 'injure', 'clearFromInjury', 'unretire']],
        'injured' => ['injured', ['release', 'clearFromInjury', 'retire'], ['employ', 'suspend', 'reinstate', 'injure', 'unretire']],
        'retired' => ['retired', ['unretire'], ['employ', 'release', 'suspend', 'reinstate', 'injure', 'clearFromInjury', 'retire']],
    ];
}

/**
 * Everything that differs between the Actions components, keyed by entity.
 *
 * - delegations: lifecycle method => [action class, flash message] for components that hand the work to an action class
 * - forbidden: [factory state, methods, period relation, whether that period exists for the untouched record]
 * - states: factory state => visible and hidden buttons
 * - transitions: [starting factory state, list of [method, button that appears, button that disappears]]
 * - eligibility: [factory state, closure asking the mounted component whether the viewer may perform the first action, wire:click method of its button]
 *
 * @return array<string, array{
 *     component: class-string,
 *     property: string,
 *     model: class-string,
 *     event: string,
 *     assertView: Closure,
 *     delegations: array<string, array{class-string, string}>,
 *     forbidden: array{string, list<string>, string, bool},
 *     states: array<string, array{string, list<string>, list<string>}>,
 *     transitions: array{string, list<array{string, string, string}>},
 *     eligibility: array{string, Closure, string},
 * }>
 */
function livewireLifecycleActionCatalog(): array
{
    $individualMethods = ['employ', 'release', 'retire', 'unretire', 'suspend', 'reinstate', 'injure', 'clearFromInjury'];
    $individualTransitions = ['unemployed', [['employ', 'retire', 'employ'], ['retire', 'unretire', 'retire']]];

    return [
        'wrestler' => [
            'component' => WrestlerActions::class,
            'property' => 'wrestler',
            'model' => Wrestler::class,
            'event' => 'wrestler-updated',
            'assertView' => static fn (Testable $livewire) => $livewire->assertViewIs('livewire.wrestlers.components.actions'),
            'delegations' => [
                'employ' => [EmployWrestler::class, 'Wrestler has been hired.'],
                'release' => [ReleaseWrestler::class, 'Contract has been terminated.'],
                'retire' => [RetireWrestler::class, 'Wrestler has been retired.'],
                'unretire' => [UnretireWrestler::class, 'Wrestler has been brought out of retirement.'],
                'suspend' => [SuspendWrestler::class, 'Wrestler has been suspended.'],
                'reinstate' => [ReinstateWrestler::class, 'Wrestler has been reinstated.'],
                'injure' => [InjureWrestler::class, 'Injury has been recorded.'],
                'clearFromInjury' => [ClearWrestlerFromInjury::class, 'Wrestler has been cleared from injury.'],
            ],
            'forbidden' => ['unemployed', $individualMethods, 'currentEmployment', false],
            'states' => livewireIndividualButtonStates(),
            'transitions' => $individualTransitions,
            'eligibility' => ['unemployed', static fn (WrestlerActions $component): bool => $component->canPerform(RosterLifecycleAction::Employ), 'employ'],
        ],
        'referee' => [
            'component' => RefereeActions::class,
            'property' => 'referee',
            'model' => Referee::class,
            'event' => 'referee-updated',
            'assertView' => static fn (Testable $livewire) => $livewire->assertViewIs('livewire.referees.components.actions'),
            'delegations' => [
                'employ' => [EmployReferee::class, 'Referee has been hired.'],
                'release' => [ReleaseReferee::class, 'Contract has been terminated.'],
                'retire' => [RetireReferee::class, 'Referee has been retired.'],
                'unretire' => [UnretireReferee::class, 'Referee has been brought out of retirement.'],
                'suspend' => [SuspendReferee::class, 'Referee has been suspended.'],
                'reinstate' => [ReinstateReferee::class, 'Referee has been reinstated.'],
                'injure' => [InjureReferee::class, 'Injury has been recorded.'],
                'clearFromInjury' => [ClearRefereeFromInjury::class, 'Referee has been cleared from injury.'],
            ],
            'forbidden' => ['unemployed', $individualMethods, 'currentEmployment', false],
            'states' => livewireIndividualButtonStates(),
            'transitions' => $individualTransitions,
            'eligibility' => ['unemployed', static fn (RefereeActions $component): bool => $component->canPerform(RosterLifecycleAction::Employ), 'employ'],
        ],
        'manager' => [
            'component' => ManagerActions::class,
            'property' => 'manager',
            'model' => Manager::class,
            'event' => 'manager-updated',
            'assertView' => static fn (Testable $livewire) => $livewire->assertViewIs('livewire.managers.components.actions'),
            'delegations' => [
                'employ' => [EmployManager::class, 'Manager has been hired.'],
                'release' => [ReleaseManager::class, 'Manager contract has been terminated.'],
                'retire' => [RetireManager::class, 'Manager has been retired.'],
                'unretire' => [UnretireManager::class, 'Manager has been brought out of retirement.'],
                'suspend' => [SuspendManager::class, 'Manager has been suspended.'],
                'injure' => [InjureManager::class, 'Manager injury has been recorded.'],
                'clearFromInjury' => [ClearManagerFromInjury::class, 'Manager has been cleared from injury.'],
            ],
            'forbidden' => ['unemployed', $individualMethods, 'currentEmployment', false],
            'states' => livewireIndividualButtonStates(),
            'transitions' => $individualTransitions,
            'eligibility' => ['unemployed', static fn (ManagerActions $component): bool => $component->canPerform(RosterLifecycleAction::Employ), 'employ'],
        ],
        'tag team' => [
            'component' => TagTeamActions::class,
            'property' => 'tagTeam',
            'model' => TagTeam::class,
            'event' => 'tag-team-updated',
            'assertView' => static fn (Testable $livewire) => $livewire->assertViewIs('livewire.tag-teams.components.actions'),
            'delegations' => [
                'employ' => [EmployTagTeam::class, 'Tag team has been hired.'],
                'release' => [ReleaseTagTeam::class, 'Tag team contract has been terminated.'],
                'retire' => [RetireTagTeam::class, 'Tag team has been retired.'],
                'unretire' => [UnretireTagTeam::class, 'Tag team has been brought out of retirement.'],
                'suspend' => [SuspendTagTeam::class, 'Tag team has been suspended.'],
                'reinstate' => [ReinstateTagTeam::class, 'Tag team has been reinstated.'],
            ],
            'forbidden' => ['unemployed', ['employ', 'release', 'retire', 'unretire', 'suspend', 'reinstate'], 'currentEmployment', false],
            'states' => [
                'unemployed' => ['unemployed', ['employ'], ['release', 'suspend', 'reinstate', 'retire', 'unretire']],
                'employed' => ['employed', ['release', 'suspend', 'retire'], ['employ', 'reinstate', 'unretire']],
                'suspended' => ['suspended', ['reinstate'], ['employ', 'suspend', 'unretire']],
                'retired' => ['retired', ['unretire'], ['employ', 'release', 'suspend', 'reinstate', 'retire']],
            ],
            'transitions' => ['employed', [['suspend', 'reinstate', 'suspend']]],
            'eligibility' => ['unemployed', static fn (TagTeamActions $component): bool => $component->canPerform(RosterLifecycleAction::Employ), 'employ'],
        ],
        'title' => [
            'component' => TitleActions::class,
            'property' => 'title',
            'model' => Title::class,
            'event' => 'title-updated',
            'assertView' => static fn (Testable $livewire) => $livewire->assertViewIs('livewire.titles.components.actions'),
            'delegations' => [
                'debut' => [DebutTitle::class, 'Title successfully debuted.'],
                'retire' => [RetireTitle::class, 'Title successfully retired.'],
                'unretire' => [UnretireTitle::class, 'Title successfully unretired.'],
                'deactivate' => [PullTitle::class, 'Title successfully pulled.'],
                'reinstate' => [ReinstateTitle::class, 'Title successfully reinstated.'],
            ],
            'forbidden' => ['undebuted', ['debut', 'retire', 'unretire', 'deactivate', 'reinstate'], 'currentActivityPeriod', false],
            'states' => [
                'undebuted' => ['undebuted', ['debut'], ['retire', 'unretire', 'deactivate', 'reinstate']],
                'active' => ['active', ['retire', 'deactivate'], ['debut', 'unretire', 'reinstate']],
                'inactive' => ['inactive', ['retire', 'reinstate'], ['debut', 'unretire', 'deactivate']],
                'scheduled debut' => ['withFutureDebut', [], ['debut', 'retire', 'unretire', 'deactivate', 'reinstate']],
                'retired' => ['retired', ['unretire'], ['debut', 'retire', 'deactivate', 'reinstate']],
            ],
            'transitions' => ['undebuted', [['debut', 'deactivate', 'debut']]],
            'eligibility' => ['undebuted', static fn (TitleActions $component): bool => $component->canPerform(TitleLifecycleTransition::Debut), 'debut'],
        ],
        'stable' => [
            'component' => StableActions::class,
            'property' => 'stable',
            'model' => Stable::class,
            'event' => 'stable-updated',
            'assertView' => static fn (Testable $livewire) => $livewire->assertViewIs('livewire.stables.components.actions'),
            'delegations' => [],
            'forbidden' => ['active', ['establish', 'disband', 'retire', 'unretire'], 'currentActivityPeriod', true],
            'states' => [
                'unformed' => ['withNoMembers', [], ['establish', 'disband', 'retire', 'unretire']],
                'ready to establish' => ['withEmployedDefaultMembers', ['establish'], ['disband', 'retire', 'unretire']],
                'active' => ['active', ['disband', 'retire'], ['establish', 'unretire']],
                'disbanded' => ['inactive', ['retire'], ['establish', 'disband', 'unretire']],
                'retired' => ['retired', ['unretire'], ['establish', 'disband', 'retire']],
            ],
            'transitions' => ['active', [['disband', 'retire', 'disband']]],
            'eligibility' => ['active', static fn (StableActions $component): bool => $component->canPerform(StableLifecycleAction::Disband), 'disband'],
        ],
    ];
}

dataset('lifecycle action components', function (): Generator {
    foreach (livewireLifecycleActionCatalog() as $entity => $spec) {
        yield $entity => [
            'component' => $spec['component'],
            'property' => $spec['property'],
            'model' => $spec['model'],
            'assertView' => $spec['assertView'],
        ];
    }
});

dataset('lifecycle action delegations', function (): Generator {
    foreach (livewireLifecycleActionCatalog() as $entity => $spec) {
        foreach ($spec['delegations'] as $method => [$actionClass, $message]) {
            yield "{$entity} {$method}" => [
                'component' => $spec['component'],
                'property' => $spec['property'],
                'model' => $spec['model'],
                'event' => $spec['event'],
                'method' => $method,
                'actionClass' => $actionClass,
                'action' => Double::for($actionClass),
                'message' => $message,
            ];
        }
    }
});

dataset('lifecycle action refusals', function (): Generator {
    foreach (livewireLifecycleActionCatalog() as $entity => $spec) {
        [$state, $methods, $period, $periodExists] = $spec['forbidden'];

        foreach ($methods as $method) {
            yield "{$entity} {$method}" => [
                'component' => $spec['component'],
                'property' => $spec['property'],
                'model' => $spec['model'],
                'event' => $spec['event'],
                'state' => $state,
                'method' => $method,
                'period' => $period,
                'periodExists' => $periodExists,
            ];
        }
    }
});

dataset('lifecycle action buttons by state', function (): Generator {
    foreach (livewireLifecycleActionCatalog() as $entity => $spec) {
        foreach ($spec['states'] as $label => [$state, $visible, $hidden]) {
            yield "{$entity} {$label}" => [
                'component' => $spec['component'],
                'property' => $spec['property'],
                'model' => $spec['model'],
                'state' => $state,
                'visible' => $visible,
                'hidden' => $hidden,
            ];
        }
    }
});

dataset('lifecycle action button transitions', function (): Generator {
    foreach (livewireLifecycleActionCatalog() as $entity => $spec) {
        [$state, $steps] = $spec['transitions'];

        yield $entity => [
            'component' => $spec['component'],
            'property' => $spec['property'],
            'model' => $spec['model'],
            'event' => $spec['event'],
            'state' => $state,
            'steps' => $steps,
        ];
    }
});

dataset('lifecycle action eligibility', function (): Generator {
    foreach (livewireLifecycleActionCatalog() as $entity => $spec) {
        [$state, $canPerform, $method] = $spec['eligibility'];

        yield $entity => [
            'component' => $spec['component'],
            'property' => $spec['property'],
            'model' => $spec['model'],
            'state' => $state,
            'canPerform' => $canPerform,
            'method' => $method,
        ];
    }
});
