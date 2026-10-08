<?php

declare(strict_types=1);

use App\Actions\Managers\InjureAction as InjureManagerAction;
use App\Actions\Referees\InjureAction as InjureRefereeAction;
use App\Enums\Users\UserStatus;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Policies\EventMatchPolicy;
use App\Policies\EventPolicy;
use App\Policies\ManagerPolicy;
use App\Policies\RefereePolicy;
use App\Policies\StablePolicy;
use App\Policies\TagTeamPolicy;
use App\Policies\TitlePolicy;
use App\Policies\UserPolicy;
use App\Policies\VenuePolicy;
use App\Policies\WrestlerPolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Every ability declared by each model policy, split by whether the Gate receives the model class or an instance.
 *
 * Each of these policies denies every ability on its own; administrators are allowed by the global Gate::before hook.
 *
 * @return array<class-string, array{model: class-string, create: Closure(): Model, class: list<string>, instance: list<string>}>
 */
function policyAbilityCatalog(): array
{
    $crud = ['view', 'update', 'delete', 'restore'];
    $rosterMember = [...$crud, 'employ', 'release', 'retire', 'unretire', 'suspend', 'reinstate', 'injure', 'clearFromInjury'];

    return [
        EventMatchPolicy::class => ['model' => EventMatch::class, 'create' => fn (): EventMatch => EventMatch::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => $crud],
        EventPolicy::class => ['model' => Event::class, 'create' => fn (): Event => Event::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => $crud],
        ManagerPolicy::class => ['model' => Manager::class, 'create' => fn (): Manager => Manager::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => $rosterMember],
        RefereePolicy::class => ['model' => Referee::class, 'create' => fn (): Referee => Referee::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => $rosterMember],
        StablePolicy::class => ['model' => Stable::class, 'create' => fn (): Stable => Stable::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => [...$crud, 'establish', 'disband', 'retire', 'unretire', 'merge', 'split', 'reunite']],
        TagTeamPolicy::class => ['model' => TagTeam::class, 'create' => fn (): TagTeam => TagTeam::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => [...$crud, 'employ', 'release', 'suspend', 'reinstate', 'retire', 'unretire']],
        TitlePolicy::class => ['model' => Title::class, 'create' => fn (): Title => Title::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => [...$crud, 'debut', 'pull', 'reinstate', 'retire', 'unretire']],
        UserPolicy::class => ['model' => User::class, 'create' => fn (): User => User::factory()->create(), 'class' => ['viewAny', 'create', 'manageUsers'], 'instance' => $crud],
        VenuePolicy::class => ['model' => Venue::class, 'create' => fn (): Venue => Venue::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => $crud],
        WrestlerPolicy::class => ['model' => Wrestler::class, 'create' => fn (): Wrestler => Wrestler::factory()->create(), 'class' => ['viewAny', 'create'], 'instance' => $rosterMember],
    ];
}

/*
 * One case per policy: its class, model class, class abilities and instance abilities.
 */
dataset('model policies', function (): Generator {
    foreach (policyAbilityCatalog() as $policy => $catalog) {
        yield class_basename($policy) => [$policy, $catalog['model'], $catalog['class'], $catalog['instance']];
    }
});

/*
 * One case per (policy, ability): the policy class, model class, ability and whether the Gate receives a model
 * instance (true) or only the model class (false).
 */
dataset('policy abilities', function (): Generator {
    foreach (policyAbilityCatalog() as $policy => $catalog) {
        foreach ($catalog['class'] as $ability) {
            yield class_basename($policy)."::{$ability}" => [$policy, $catalog['model'], $ability, false];
        }

        foreach ($catalog['instance'] as $ability) {
            yield class_basename($policy)."::{$ability}" => [$policy, $catalog['model'], $ability, true];
        }
    }
});

/*
 * What the Gate::before hook returns when no subject is given: administrators are decided (true), everyone else
 * falls through to the policies (null).
 */
dataset('gate hook decisions', [
    'administrator' => ['administrator', true],
    'basic user' => ['basic user', null],
]);

/*
 * What the Gate decides for a policy ability on a subject: administrators are allowed, basic users are denied.
 */
dataset('gate subject decisions', [
    'administrator' => ['administrator', true],
    'basic user' => ['basic user', false],
]);

/*
 * Ability names no policy declares: the Gate::before hook still decides them for administrators.
 */
dataset('abilities without a policy method', [
    'custom-ability', 'nonexistentAbility', 'any-ability', 'any_ability', 'any-operation',
    'assignToMatch', 'removeFromMatch', 'viewMatchHistory',
    'assignToWrestler', 'assignToTagTeam', 'removeFromAssignment',
    'assignChampion', 'vacate', 'defendTitle',
    'viewProfile', 'changePassword', 'manageRoles', 'deactivate', 'activate', 'resetPassword', 'changeRole', 'viewAuditLog',
    'read', 'custom', 'manage', 'any',
]);

/*
 * Each case creates a model in one of the states its policy has to treat like any other: the policy class and a
 * closure returning the persisted model.
 */
dataset('policy subject states', [
    'bookable referee' => [RefereePolicy::class, fn (): Referee => Referee::factory()->bookable()->create()],
    'injured referee' => [RefereePolicy::class, fn (): Referee => Referee::factory()->injured()->create()],
    'retired referee' => [RefereePolicy::class, fn (): Referee => Referee::factory()->retired()->create()],
    'suspended referee' => [RefereePolicy::class, fn (): Referee => Referee::factory()->suspended()->create()],
    'referee injured through the action' => [RefereePolicy::class, function (): Referee {
        $referee = Referee::factory()->bookable()->create();
        resolve(InjureRefereeAction::class)->handle($referee, now());

        return $referee;
    }],
    'employed manager' => [ManagerPolicy::class, fn (): Manager => Manager::factory()->employed()->create()],
    'injured manager' => [ManagerPolicy::class, fn (): Manager => Manager::factory()->injured()->create()],
    'retired manager' => [ManagerPolicy::class, fn (): Manager => Manager::factory()->retired()->create()],
    'suspended manager' => [ManagerPolicy::class, fn (): Manager => Manager::factory()->suspended()->create()],
    'manager injured through the action' => [ManagerPolicy::class, function (): Manager {
        $manager = Manager::factory()->employed()->create();
        resolve(InjureManagerAction::class)->handle($manager, now());

        return $manager;
    }],
    'active stable' => [StablePolicy::class, fn (): Stable => Stable::factory()->active()->create()],
    'inactive stable' => [StablePolicy::class, fn (): Stable => Stable::factory()->inactive()->create()],
    'disbanded stable' => [StablePolicy::class, fn (): Stable => Stable::factory()->disbanded()->create()],
    'retired stable' => [StablePolicy::class, fn (): Stable => Stable::factory()->retired()->create()],
    'trashed stable' => [StablePolicy::class, fn (): Stable => Stable::factory()->trashed()->create()],
    'employed tag team' => [TagTeamPolicy::class, fn (): TagTeam => TagTeam::factory()->employed()->make()],
    'unemployed tag team' => [TagTeamPolicy::class, fn (): TagTeam => TagTeam::factory()->unemployed()->make()],
    'suspended tag team' => [TagTeamPolicy::class, fn (): TagTeam => TagTeam::factory()->suspended()->make()],
    'retired tag team' => [TagTeamPolicy::class, fn (): TagTeam => TagTeam::factory()->retired()->make()],
    'released tag team' => [TagTeamPolicy::class, fn (): TagTeam => TagTeam::factory()->released()->make()],
    'tag team with future employment' => [TagTeamPolicy::class, fn (): TagTeam => TagTeam::factory()->futureEmployment()->make()],
    'trashed tag team' => [TagTeamPolicy::class, fn (): TagTeam => TagTeam::factory()->trashed()->make()],
    'singles title' => [TitlePolicy::class, fn (): Title => Title::factory()->singles()->create()],
    'tag team title' => [TitlePolicy::class, fn (): Title => Title::factory()->tagTeam()->create()],
    'active title' => [TitlePolicy::class, fn (): Title => Title::factory()->active()->create()],
    'retired title' => [TitlePolicy::class, fn (): Title => Title::factory()->retired()->create()],
    'undebuted title' => [TitlePolicy::class, fn (): Title => Title::factory()->create()],
    'administrator user' => [UserPolicy::class, administrator(...)],
    'basic user' => [UserPolicy::class, basicUser(...)],
    'active user' => [UserPolicy::class, fn (): User => User::factory()->create(['status' => UserStatus::Active])],
    'inactive user' => [UserPolicy::class, fn (): User => User::factory()->create(['status' => UserStatus::Inactive])],
    'unverified user' => [UserPolicy::class, fn (): User => User::factory()->create(['status' => UserStatus::Unverified])],
]);
