<?php

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Livewire\Promotions\Members\Manage;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

function attachPromotionMember(User $user, Promotion $promotion, MembershipRole $role, MembershipStatus $status = MembershipStatus::Active): void
{
    $promotion->users()->attach($user, [
        'role' => $role,
        'status' => $status,
    ]);
}

/**
 * @return list<array{modelClass: class-string<Model>, model: Model, abilities: list<string>}>
 */
function createPromotionAuthorizationSubjects(Promotion $promotion): array
{
    $event = Event::factory()->for($promotion, 'promotion')->create();

    return [
        [
            'modelClass' => Wrestler::class,
            'model' => Wrestler::factory()->for($promotion, 'promotion')->create(),
            'abilities' => ['update', 'delete', 'restore', 'employ', 'release', 'retire', 'unretire', 'suspend', 'reinstate', 'injure', 'clearFromInjury'],
        ],
        [
            'modelClass' => Manager::class,
            'model' => Manager::factory()->for($promotion, 'promotion')->create(),
            'abilities' => ['update', 'delete', 'restore', 'employ', 'release', 'retire', 'unretire', 'suspend', 'reinstate', 'injure', 'clearFromInjury'],
        ],
        [
            'modelClass' => Referee::class,
            'model' => Referee::factory()->for($promotion, 'promotion')->create(),
            'abilities' => ['update', 'delete', 'restore', 'employ', 'release', 'retire', 'unretire', 'suspend', 'reinstate', 'injure', 'clearFromInjury'],
        ],
        [
            'modelClass' => TagTeam::class,
            'model' => TagTeam::factory()->for($promotion, 'promotion')->create(),
            'abilities' => ['update', 'delete', 'restore', 'employ', 'release', 'suspend', 'reinstate', 'retire', 'unretire'],
        ],
        [
            'modelClass' => Stable::class,
            'model' => Stable::factory()->for($promotion, 'promotion')->create(),
            'abilities' => ['update', 'delete', 'restore', 'establish', 'disband', 'retire', 'unretire'],
        ],
        [
            'modelClass' => Event::class,
            'model' => $event,
            'abilities' => ['update', 'delete', 'restore'],
        ],
        [
            'modelClass' => EventMatch::class,
            'model' => EventMatch::factory()->forEvent($event)->create(),
            'abilities' => ['update', 'delete', 'restore'],
        ],
        [
            'modelClass' => Title::class,
            'model' => Title::factory()->for($promotion, 'promotion')->create(),
            'abilities' => ['update', 'delete', 'restore', 'debut', 'pull', 'reinstate', 'retire', 'unretire', 'activate', 'deactivate'],
        ],
    ];
}

test('members can view their promotion data but cannot manage it', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $user = basicUser();
    $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
    $otherWrestler = Wrestler::factory()->for($otherPromotion, 'promotion')->create();
    attachPromotionMember($user, $promotion, MembershipRole::Member);

    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    expect(Gate::forUser($user)->allows('view', $promotion))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', Wrestler::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $wrestler))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Wrestler::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $wrestler))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', $otherWrestler))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', Venue::class))->toBeFalse();

    Livewire::actingAs($user)
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->assertDontSee('Add an existing user')
        ->assertDontSee('Save role')
        ->assertSee('Member')
        ->call('addMember', User::factory()->create(['status' => UserStatus::Active])->id)
        ->assertForbidden();
});

test('managers can manage promotion data but cannot change promotion settings or memberships', function () {
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
    attachPromotionMember($user, $promotion, MembershipRole::Manager);

    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    expect(Gate::forUser($user)->allows('create', Wrestler::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $wrestler))->toBeTrue()
        ->and(Gate::forUser($user)->allows('retire', $wrestler))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $promotion))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manageMembers', $promotion))->toBeFalse();

    Livewire::actingAs($user)
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->assertDontSee('Add an existing user')
        ->assertDontSee('Save role')
        ->assertSee('Manager')
        ->call('updateMemberStatus', User::factory()->create()->id, MembershipStatus::Suspended->value)
        ->assertForbidden();
});

test('owners can update promotion settings and manage membership roles', function () {
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    attachPromotionMember($user, $promotion, MembershipRole::Owner);

    expect(Gate::forUser($user)->allows('view', $promotion))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $promotion))->toBeTrue()
        ->and(Gate::forUser($user)->allows('manageMembers', $promotion))->toBeTrue();

    $newMember = User::factory()->create(['status' => UserStatus::Active]);

    Livewire::actingAs($user)
        ->test(Manage::class, ['promotionId' => $promotion->id])
        ->assertSee('Add an existing user')
        ->assertSee('Save role')
        ->call('addMember', $newMember->id)
        ->assertHasNoErrors();

    expect($promotion->hasActiveMember($newMember))->toBeTrue();
});

test('suspended promotion members no longer have access', function () {
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    attachPromotionMember($user, $promotion, MembershipRole::Owner, MembershipStatus::Suspended);

    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    expect(Gate::forUser($user)->allows('view', $promotion))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', Wrestler::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manageMembers', $promotion))->toBeFalse();
});

test('promotion roles apply the complete permission matrix across every owned resource', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();
    $ownedSubjects = createPromotionAuthorizationSubjects($promotion);
    $foreignSubjects = createPromotionAuthorizationSubjects($otherPromotion);

    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    $roles = [
        ['role' => MembershipRole::Member, 'canManage' => false],
        ['role' => MembershipRole::Manager, 'canManage' => true],
        ['role' => MembershipRole::Owner, 'canManage' => true],
    ];

    foreach ($roles as ['role' => $role, 'canManage' => $canManage]) {
        $user = basicUser();
        attachPromotionMember($user, $promotion, $role);

        foreach ($ownedSubjects as $index => $subject) {
            $foreignSubject = $foreignSubjects[$index];
            $label = "{$role->value} on {$subject['modelClass']}";

            expect(Gate::forUser($user)->allows('viewAny', $subject['modelClass']))
                ->toBeTrue("{$label} can view the resource list")
                ->and(Gate::forUser($user)->allows('create', $subject['modelClass']))
                ->toBe($canManage, "{$label} create permission follows its role")
                ->and(Gate::forUser($user)->allows('view', $subject['model']))
                ->toBeTrue("{$label} can view its promotion's record")
                ->and(Gate::forUser($user)->allows('view', $foreignSubject['model']))
                ->toBeFalse("{$label} cannot view another promotion's record");

            foreach ($subject['abilities'] as $ability) {
                expect(Gate::forUser($user)->allows($ability, $subject['model']))
                    ->toBe($canManage, "{$label} {$ability} permission follows its role");
            }
        }
    }
});
