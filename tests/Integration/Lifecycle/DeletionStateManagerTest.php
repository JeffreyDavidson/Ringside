<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Lifecycle\DeletionStateManager;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it records deletion and restoration for every soft-deletable owner', function (LifecycleOwnerType $ownerType) {
    $owner = match ($ownerType) {
        LifecycleOwnerType::Event => Event::factory()->create(),
        LifecycleOwnerType::Manager => Manager::factory()->create(),
        LifecycleOwnerType::Match => EventMatch::factory()->create(),
        LifecycleOwnerType::Referee => Referee::factory()->create(),
        LifecycleOwnerType::Stable => Stable::factory()->create(),
        LifecycleOwnerType::TagTeam => TagTeam::factory()->create(),
        LifecycleOwnerType::Title => Title::factory()->create(),
        LifecycleOwnerType::Venue => Venue::factory()->create(),
        LifecycleOwnerType::Wrestler => Wrestler::factory()->create(),
    };
    $deletedAt = now()->subDay();
    $restoredAt = now();

    resolve(DeletionStateManager::class)->delete($owner, $deletedAt);
    resolve(DeletionStateManager::class)->restore($owner, $restoredAt);

    expect($owner->trashed())->toBeFalse();

    $deletedTransition = $owner->lifecycleTransitions()
        ->where('transition', LifecycleTransitionType::Deleted)
        ->sole();
    $restoredTransition = $owner->lifecycleTransitions()
        ->where('transition', LifecycleTransitionType::Restored)
        ->sole();

    expect($owner->lifecycleTransitions()->count())->toBe(2)
        ->and($deletedTransition->dimension)->toBe(LifecycleDimension::Deletion)
        ->and($deletedTransition->effective_at->toDateTimeString())->toBe($deletedAt->toDateTimeString())
        ->and($restoredTransition->dimension)->toBe(LifecycleDimension::Deletion)
        ->and($restoredTransition->effective_at->toDateTimeString())->toBe($restoredAt->toDateTimeString());
})->with(LifecycleOwnerType::cases());
