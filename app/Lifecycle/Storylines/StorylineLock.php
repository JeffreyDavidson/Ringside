<?php

declare(strict_types=1);

namespace App\Lifecycle\Storylines;

use App\Exceptions\RecordChangedException;
use App\Models\Storylines\Storyline;

class StorylineLock
{
    public function __construct(private readonly StorylineStateMachine $states) {}

    public function lock(Storyline $storyline, int $version, bool $allowClosed = false): Storyline
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
