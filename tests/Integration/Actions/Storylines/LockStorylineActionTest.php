<?php

declare(strict_types=1);
use App\Actions\Storylines\LockStorylineAction;
use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;

use function Pest\Laravel\actingAs;

test('closed stories refuse ordinary edits even when a client holds a current version', function (StorylineStatus $status) {
    actingAs(administrator());
    $story = Storyline::factory()->create(['status' => $status]);
    expect(fn () => resolve(LockStorylineAction::class)->handle($story, 1))->toThrow(StorylineConflictException::class)
        ->and(resolve(LockStorylineAction::class)->handle($story, 1, true)->id)->toBe($story->id);
})->with([StorylineStatus::Completed, StorylineStatus::Archived]);
