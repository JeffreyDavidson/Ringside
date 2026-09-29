<?php

declare(strict_types=1);

use App\Actions\Titles\DeleteAction;
use App\Models\Titles\Title;

test('it soft deletes a title', function (): void {
    $title = Title::factory()->create();

    resolve(DeleteAction::class)->handle($title);

    expect(Title::query()->find($title->id))->toBeNull()
        ->and(Title::withTrashed()->find($title->id))->not->toBeNull();
});

test('it closes the open lifecycle period when deleting a title', function (Closure $makeTitle, string $periodRelation) {
    $title = $makeTitle();
    $deletedAt = now()->subHour()->startOfSecond();

    resolve(DeleteAction::class)->handle($title, $deletedAt);

    $title->refresh();

    expect($title->trashed())->toBeTrue()
        ->and($title->currentActivityPeriod()->exists())->toBeFalse()
        ->and($title->currentRetirement()->exists())->toBeFalse()
        ->and($title->{$periodRelation}()->latest('id')->firstOrFail()->ended_at?->toDateTimeString())
        ->toBe($deletedAt->toDateTimeString());
})->with([
    'active title ends its activity period' => [
        fn (): Title => Title::factory()->active()->create(),
        'activityPeriods',
    ],
    'retired title ends its retirement' => [
        fn (): Title => Title::factory()->retired()->create(),
        'retirements',
    ],
]);

test('it leaves closed lifecycle periods untouched when deleting an inactive title', function () {
    $title = Title::factory()->inactive()->create();
    $originalEndedAt = $title->activityPeriods()->firstOrFail()->ended_at;

    resolve(DeleteAction::class)->handle($title, now()->subHour());

    expect($title->activityPeriods()->firstOrFail()->ended_at?->toDateTimeString())
        ->toBe($originalEndedAt?->toDateTimeString());
});
