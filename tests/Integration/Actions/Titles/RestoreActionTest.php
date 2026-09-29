<?php

declare(strict_types=1);

use App\Actions\Titles\DeleteAction;
use App\Actions\Titles\RestoreAction;
use App\Enums\BusinessRuleReason;
use App\Exceptions\Titles\CannotBeRestoredException;
use App\Models\Titles\Title;

test('it restores a soft-deleted title', function (): void {
    $title = Title::factory()->create();
    resolve(DeleteAction::class)->handle($title);

    $deletedTitle = Title::withTrashed()->findOrFail($title->id);
    resolve(RestoreAction::class)->handle($deletedTitle);

    expect(Title::query()->find($title->id))->not->toBeNull()
        ->and(Title::withTrashed()->findOrFail($title->id)->deleted_at)->toBeNull();
});

test('it rejects restoring a title that is not deleted', function (): void {
    $title = Title::factory()->create();
    $exception = null;

    try {
        resolve(RestoreAction::class)->handle($title);
    } catch (CannotBeRestoredException $caught) {
        $exception = $caught;
    }

    expect($exception?->reason())->toBe(BusinessRuleReason::NotDeleted)
        ->and(Title::query()->find($title->id))->not->toBeNull();
});
