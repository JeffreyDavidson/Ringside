<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Facades\Gate;

class LockStorylineAction
{
    public function handle(Storyline $storyline, int $version, bool $allowClosed = false): Storyline
    {
        $locked = Storyline::query()->whereKey($storyline->id)->lockForUpdate()->firstOrFail();
        Gate::authorize('update', $locked);
        if ($locked->version !== $version) {
            throw StorylineConflictException::changed();
        }
        if (! $allowClosed && ! in_array($locked->status, [StorylineStatus::Active, StorylineStatus::Paused], true)) {
            throw StorylineConflictException::state();
        }

        return $locked;
    }
}
