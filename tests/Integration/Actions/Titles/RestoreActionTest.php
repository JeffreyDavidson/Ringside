<?php

declare(strict_types=1);

use App\Actions\Titles\DeleteAction;
use App\Actions\Titles\RestoreAction;
use App\Models\Titles\Title;

test('it restores a soft-deleted title', function (): void {
    $title = Title::factory()->create();
    resolve(DeleteAction::class)->handle($title);

    $deletedTitle = Title::withTrashed()->findOrFail($title->id);
    resolve(RestoreAction::class)->handle($deletedTitle);

    expect(Title::query()->find($title->id))->not->toBeNull()
        ->and(Title::withTrashed()->findOrFail($title->id)->deleted_at)->toBeNull();
});
