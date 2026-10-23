<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Exceptions\RecordChangedException;
use App\Lifecycle\Storylines\StorylineStateMachine;
use App\Models\Storylines\Storyline;

class LockStorylineAction
{
    public function __construct(private readonly StorylineStateMachine $states) {}

    public function handle(Storyline $storyline, int $version, bool $allowClosed = false): Storyline
    {
        $locked = $storyline->refreshForUpdate();

        if ($locked->version !== $version) {
            throw RecordChangedException::changed();
        }

        if (! $allowClosed) {
            $this->states->ensureOpen($locked);
        }

        return $locked;
    }
}
