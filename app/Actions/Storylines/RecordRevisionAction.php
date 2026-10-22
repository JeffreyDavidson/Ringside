<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Enums\Storylines\StorylineRevisionOperation;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineParticipant;
use App\Models\Storylines\StorylineRevision;
use App\Models\Storylines\StorylineStep;
use App\Models\Users\User;

class RecordRevisionAction
{
    /** @param array<string, mixed> $before */
    public function handle(Storyline $storyline, Storyline|StorylineStep|StorylineParticipant|StorylineDevelopment $record, StorylineRevisionOperation $operation, User $actor, array $before = [], ?string $reason = null): void
    {
        $revision = new StorylineRevision;
        $revision->promotion_id = $storyline->promotion_id;
        $revision->storyline_id = $storyline->id;
        $revision->actor_id = $actor->id;
        $revision->operation = $operation;
        $revision->record_type = class_basename($record);
        $revision->record_id = $record->id;
        $revision->reason = $reason;
        $revision->before = $before;
        $revision->after = $record->attributesToArray();
        $revision->save();
        $storyline->version++;
        $storyline->save();
    }
}
