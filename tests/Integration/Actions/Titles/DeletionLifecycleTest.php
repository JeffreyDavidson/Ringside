<?php

declare(strict_types=1);

use App\Actions\Titles\DeleteAction;
use App\Actions\Titles\RestoreAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
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
