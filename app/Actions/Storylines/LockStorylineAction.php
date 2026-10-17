<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;

class LockStorylineAction
{
    public function handle(Storyline $storyline, int $version, bool $allowClosed = false): Storyline
    {
        $locked = Storyline::query()->whereKey($storyline->id)->lockForUpdate()->firstOrFail();
        if ($locked->version !== $version) {
            throw RecordChangedException::changed();
        }
        if (! $allowClosed && ! in_array($locked->status, [StorylineStatus::Active, StorylineStatus::Paused], true)) {
            throw StorylineConflictException::state();
        }

        return $locked;
    }
}
