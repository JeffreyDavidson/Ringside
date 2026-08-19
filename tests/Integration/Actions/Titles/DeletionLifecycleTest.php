<?php

declare(strict_types=1);

use App\Actions\Titles\DeleteAction;
use App\Actions\Titles\RestoreAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Titles\CannotBeRestoredException;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('deleting a title ends its current championship reign', function () {
    $title = Title::factory()->create();
    $championship = TitleChampionship::factory()
        ->for($title)
        ->current()
        ->create();
    $deletedAt = now()->subDay()->startOfSecond();

    resolve(DeleteAction::class)->handle($title, $deletedAt);

    expect($championship->refresh()->lost_at)->toEqual($deletedAt)
        ->and($title->refresh()->trashed())->toBeTrue();
});

test('it audits title deletion and restoration', function () {
    $title = Title::factory()->create();
    $deletedAt = now()->subDay();

    resolve(DeleteAction::class)->handle($title, $deletedAt);
    resolve(RestoreAction::class)->handle($title);

    $deletedTransition = $title->lifecycleTransitions()
        ->where('transition', LifecycleTransitionType::Deleted)
        ->sole();
    $restoredTransition = $title->lifecycleTransitions()
        ->where('transition', LifecycleTransitionType::Restored)
        ->sole();

    expect($title->trashed())->toBeFalse()
        ->and($title->lifecycleTransitions()->count())->toBe(2)
        ->and($deletedTransition->dimension)->toBe(LifecycleDimension::Deletion)
        ->and($deletedTransition->effective_at->toDateTimeString())->toBe($deletedAt->toDateTimeString())
        ->and($restoredTransition->dimension)->toBe(LifecycleDimension::Deletion)
        ->and($restoredTransition->effective_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('it rejects restoring a title into an active name conflict', function () {
    $title = Title::factory()->create(['name' => 'World Title']);

    resolve(DeleteAction::class)->handle($title);
    Title::factory()->create(['name' => 'World Title']);

    expect(fn () => resolve(RestoreAction::class)->handle($title))
        ->toThrow(CannotBeRestoredException::class);

    expect(Title::onlyTrashed()->whereKey($title->getKey())->exists())->toBeTrue();
});
