<?php

declare(strict_types=1);

use App\Actions\Stables\MergeStablesAction;
use App\Actions\Stables\RestoreAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Roster\Stables\CannotBeMergedException;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Services\Roster\Stables\StableMembershipService;

use function Pest\Laravel\assertSoftDeleted;

it('moves current members to the primary stable and preserves secondary history', function () {
    $primaryStable = Stable::factory()->active()->create();
    $secondaryStable = Stable::factory()->active()->create();
    $primaryWrestlerIds = $primaryStable->currentWrestlers()->pluck('wrestlers.id');
    $primaryTagTeamIds = $primaryStable->currentTagTeams()->pluck('tag_teams.id');
    $secondaryWrestlers = $secondaryStable->currentWrestlers()->get();
    $secondaryTagTeams = $secondaryStable->currentTagTeams()->get();
    $mergeDate = now();

    resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        $mergeDate,
    );

    expect($primaryStable->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($primaryWrestlerIds->merge($secondaryWrestlers->modelKeys())->all())
        ->and($primaryStable->currentTagTeams()->pluck('tag_teams.id')->all())
        ->toEqualCanonicalizing($primaryTagTeamIds->merge($secondaryTagTeams->modelKeys())->all())
        ->and($secondaryStable->currentWrestlers()->exists())
        ->toBeFalse()
        ->and($secondaryStable->currentTagTeams()->exists())
        ->toBeFalse()
        ->and($secondaryStable->previousWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($secondaryWrestlers->modelKeys())
        ->and($secondaryStable->previousTagTeams()->pluck('tag_teams.id')->all())
        ->toEqualCanonicalizing($secondaryTagTeams->modelKeys())
        ->and($secondaryStable->currentActivityPeriod()->exists())
        ->toBeFalse()
        ->and(requiredDate($secondaryStable->previousActivityPeriods()->firstOrFail()->ended_at)->format('Y-m-d H:i:s'))
        ->toBe($mergeDate->format('Y-m-d H:i:s'));

    assertSoftDeleted($secondaryStable);
});

it('rejects unavailable secondary members without changing either stable', function () {
    $primaryStable = Stable::factory()->active()->create();
    $secondaryStable = Stable::factory()->active()->create();
    $secondaryWrestler = $secondaryStable->currentWrestlers()->firstOrFail();
    $secondaryWrestler->suspensions()->create(['started_at' => now()]);
    $primaryMemberCount = resolve(StableMembershipService::class)->currentMembers($primaryStable)->getTotalMemberCount();
    $secondaryMemberCount = resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount();

    expect(fn () => resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        now(),
    ))->toThrow(CannotBeMergedException::class)
        ->and(resolve(StableMembershipService::class)->currentMembers($primaryStable)->getTotalMemberCount())->toBe($primaryMemberCount)
        ->and(resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount())->toBe($secondaryMemberCount)
        ->and($secondaryStable->currentActivityPeriod()->exists())->toBeTrue()
        ->and($secondaryStable->trashed())->toBeFalse();
});

it('rejects merging stables that are not both active and unretired', function (Closure $makeStables, string $message) {
    [$primaryStable, $secondaryStable] = $makeStables();
    $primaryMemberCount = resolve(StableMembershipService::class)->currentMembers($primaryStable)->getTotalMemberCount();
    $secondaryMemberCount = resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount();

    expect(fn () => resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        now(),
    ))->toThrow(CannotBeMergedException::class, $message)
        ->and(resolve(StableMembershipService::class)->currentMembers($primaryStable)->getTotalMemberCount())->toBe($primaryMemberCount)
        ->and(resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount())->toBe($secondaryMemberCount)
        ->and($secondaryStable->fresh()?->trashed())->toBeFalse();
})->with([
    'a stable merged with itself' => [
        function (): array {
            $stable = Stable::factory()->active()->create();

            return [$stable, $stable];
        },
        'cannot be merged with itself',
    ],
    'retired primary' => [
        fn (): array => [Stable::factory()->retired()->create(), Stable::factory()->active()->create()],
        'is retired and cannot receive merged members',
    ],
    'retired secondary' => [
        fn (): array => [Stable::factory()->active()->create(), Stable::factory()->retired()->create()],
        'is retired and cannot be merged',
    ],
    'inactive primary' => [
        fn (): array => [Stable::factory()->inactive()->create(), Stable::factory()->active()->create()],
        'is not currently active and cannot receive merged members',
    ],
    'inactive secondary' => [
        fn (): array => [Stable::factory()->active()->create(), Stable::factory()->inactive()->create()],
        'is not currently active and cannot be merged',
    ],
]);

it('merges regardless of which stable was created first', function (bool $primaryIsOlder) {
    $olderStable = Stable::factory()->active()->create();
    $newerStable = Stable::factory()->active()->create();
    [$primaryStable, $secondaryStable] = $primaryIsOlder
        ? [$olderStable, $newerStable]
        : [$newerStable, $olderStable];
    $primaryWrestlerIds = $primaryStable->currentWrestlers()->pluck('wrestlers.id');
    $secondaryWrestlerIds = $secondaryStable->currentWrestlers()->pluck('wrestlers.id');

    resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        now(),
    );

    expect($primaryStable->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing($primaryWrestlerIds->merge($secondaryWrestlerIds)->all())
        ->and($secondaryStable->currentWrestlers()->exists())->toBeFalse()
        ->and($primaryStable->refresh()->trashed())->toBeFalse()
        ->and($secondaryStable->refresh()->trashed())->toBeTrue();
})->with([
    'primary is the older stable' => true,
    'primary is the newer stable' => false,
]);

it('rejects merging stables of different promotions without changing either stable', function () {
    $primaryStable = Stable::factory()->active()->for(Promotion::factory(), 'promotion')->create();
    $secondaryStable = Stable::factory()->active()->for(Promotion::factory(), 'promotion')->create();
    $secondaryMemberCount = resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount();

    expect(fn () => resolve(MergeStablesAction::class)->handle(
        $primaryStable,
        $secondaryStable,
        now(),
    ))->toThrow(CannotBeMergedException::class, 'belong to different promotions')
        ->and(resolve(StableMembershipService::class)->currentMembers($secondaryStable)->getTotalMemberCount())->toBe($secondaryMemberCount)
        ->and($secondaryStable->currentActivityPeriod()->exists())->toBeTrue()
        ->and($secondaryStable->fresh()?->trashed())->toBeFalse()
        ->and($primaryStable->lifecycleTransitions()->exists())->toBeFalse();
});

it('merges stables that share a promotion', function () {
    $promotion = Promotion::factory()->create();
    $primaryStable = Stable::factory()->active()->for($promotion, 'promotion')->create();
    $secondaryStable = Stable::factory()->active()->for($promotion, 'promotion')->create();

    resolve(MergeStablesAction::class)->handle($primaryStable, $secondaryStable, now());

    expect($secondaryStable->refresh()->trashed())->toBeTrue();
});

it('records a merged transition on both stables', function () {
    $primaryStable = Stable::factory()->active()->create(['name' => 'Primary Stable']);
    $secondaryStable = Stable::factory()->active()->create(['name' => 'Secondary Stable']);
    $mergeDate = now()->subHour();

    resolve(MergeStablesAction::class)->handle($primaryStable, $secondaryStable, $mergeDate);

    $primaryTransition = $primaryStable->lifecycleTransitions()->sole();
    $secondaryTransition = $secondaryStable->lifecycleTransitions()
        ->where('transition', LifecycleTransitionType::Merged)
        ->sole();

    expect($primaryTransition->transition)->toBe(LifecycleTransitionType::Merged)
        ->and($primaryTransition->dimension)->toBe(LifecycleDimension::Activity)
        ->and($primaryTransition->effective_at->toDateTimeString())->toBe($mergeDate->toDateTimeString())
        ->and($primaryTransition->context)->toBe([
            'merged_stable_id' => $secondaryStable->id,
            'merged_stable_name' => 'Secondary Stable',
        ])
        ->and($secondaryTransition->transition)->toBe(LifecycleTransitionType::Merged)
        ->and($secondaryTransition->context)->toBe([
            'merged_into_stable_id' => $primaryStable->id,
            'merged_into_stable_name' => 'Primary Stable',
        ]);
});

it('records a deleted transition on the merged secondary stable so a later restore has a matching pair', function () {
    $primaryStable = Stable::factory()->active()->create();
    $secondaryStable = Stable::factory()->active()->create();
    $mergeDate = now()->subHour();

    resolve(MergeStablesAction::class)->handle($primaryStable, $secondaryStable, $mergeDate);

    $deletion = $secondaryStable->lifecycleTransitions()
        ->where('dimension', LifecycleDimension::Deletion)
        ->sole();

    expect($deletion->transition)->toBe(LifecycleTransitionType::Deleted)
        ->and($deletion->effective_at->toDateTimeString())->toBe($mergeDate->toDateTimeString())
        ->and($secondaryStable->lifecycleTransitions()->where('transition', LifecycleTransitionType::Merged)->count())->toBe(1)
        ->and($primaryStable->lifecycleTransitions()->where('dimension', LifecycleDimension::Deletion)->exists())->toBeFalse();

    resolve(RestoreAction::class)->handle($secondaryStable->refresh(), now());

    expect($secondaryStable->lifecycleTransitions()
        ->where('dimension', LifecycleDimension::Deletion)
        ->orderBy('id')
        ->pluck('transition')
        ->all())->toBe([LifecycleTransitionType::Deleted, LifecycleTransitionType::Restored]);
});

it('keeps a tag team and its direct wrestler together when merging', function () {
    $primaryStable = Stable::factory()->active()->create();
    $secondaryStable = Stable::factory()->active()->create();
    $tagTeam = TagTeam::factory()->employed()->create();
    $wrestler = $tagTeam->currentWrestlers()->firstOrFail();
    $secondaryStable->tagTeams()->attach($tagTeam, ['joined_at' => now()->subDay()]);
    $secondaryStable->wrestlers()->attach($wrestler, ['joined_at' => now()->subDay()]);

    resolve(MergeStablesAction::class)->handle($primaryStable, $secondaryStable, now());

    expect($primaryStable->currentTagTeams()->whereKey($tagTeam->getKey())->exists())->toBeTrue()
        ->and($primaryStable->currentWrestlers()->whereKey($wrestler->getKey())->exists())->toBeTrue()
        ->and($secondaryStable->currentTagTeams()->exists())->toBeFalse()
        ->and($secondaryStable->currentWrestlers()->exists())->toBeFalse();
});
