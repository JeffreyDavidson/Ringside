<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Builders\Roster\TagTeamBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Data\Storylines\ParticipantData;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaveParticipantAction
{
    public function __construct(private readonly LockStorylineAction $lock, private readonly RecordRevisionAction $revision) {}

    public function handle(Storyline $storyline, int $version, ParticipantData $data, ?int $participantId = null): StorylineParticipant
    {
        return DB::transaction(function () use ($storyline, $version, $data, $participantId): StorylineParticipant {
            $story = $this->lock->handle($storyline, $version);
            $person = match ($data->type) {
                'wrestler' => Wrestler::query()->where('promotion_id', $story->promotion_id)->whereKey($data->id)->when($participantId !== null, fn (WrestlerBuilder $query) => $query->withTrashed())->lockForUpdate()->firstOrFail(),
                default => TagTeam::query()->where('promotion_id', $story->promotion_id)->whereKey($data->id)->when($participantId !== null, fn (TagTeamBuilder $query) => $query->withTrashed())->lockForUpdate()->firstOrFail(),
            };
            $participant = $participantId === null ? new StorylineParticipant : $story->participants()->findOrFail($participantId);
            if ($participantId !== null && ($participant->participant_id !== $data->id || $participant->participant_type !== $data->type || $participant->left_on !== null)) {
                throw StorylineConflictException::state();
            }
            if ($data->leftOn === null && $story->participants()->where('participant_type', $data->type)->where('participant_id', $data->id)->whereNull('left_on')->when($participantId !== null, fn (Builder $query) => $query->whereKeyNot($participantId))->exists()) {
                throw StorylineConflictException::state();
            }
            $before = $participant->attributesToArray();
            $participant->promotion_id = $story->promotion_id;
            $participant->storyline_id = $story->id;
            $participant->participant_type = $person->getMorphClass();
            $participant->participant_id = $person->id;
            $participant->label = $participantId === null ? $person->name : $participant->label;
            $participant->fill([
                'role' => $data->role,
                'joined_on' => $participantId === null ? Carbon::parse($data->joinedOn) : $participant->joined_on,
                'left_on' => $data->leftOn === null ? null : Carbon::parse($data->leftOn),
            ]);
            if ($participant->left_on instanceof Carbon && $participant->left_on->lt($participant->joined_on)) {
                throw StorylineConflictException::state();
            }
            $participant->save();
            $this->revision->handle($story, $participant, $participantId === null ? StorylineRevisionOperation::ParticipantJoined : StorylineRevisionOperation::ParticipantLeft, $before, $data->reason);

            return $participant;
        }, attempts: 3);
    }
}
