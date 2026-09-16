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
