<?php

declare(strict_types=1);

namespace App\Lifecycle\Storylines;

use App\Enums\Storylines\StepStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Events\Event;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineParticipant;
use App\Models\Storylines\StorylineStep;
use App\Services\Storylines\StorylineSourceService;
use Illuminate\Support\Collection;

/**
 * What a storyline development may record: a planned step to confirm, an explained source, the date its event was held,
 * and a cast who were involved on that day. Each check refuses with StorylineConflictException and never locks or writes;
 * RecordDevelopmentAction calls them in the order it always checked and keeps the locks, the replay check and the writes.
 *
 * @phpstan-import-type SourceEvidence from StorylineSourceService
 */
final readonly class StorylineDevelopmentRequirements
{
    /** A new account can only confirm a step that is still planned; a correction does not look at the step's status. */
    public function ensureStepCanBeConfirmed(?StorylineStep $step, ?int $developmentId): void
    {
        if ($step instanceof StorylineStep && $developmentId === null && $step->status !== StepStatus::Planned) {
            throw StorylineConflictException::state();
        }
    }

    /**
     * A source with any warning, or one that no longer matches what the step saved, must be explained with a reason.
     * MySQL reorders JSON object keys, so the saved and current context are compared by sorted keys; value types and
     * list order still matter.
     *
     * @param  SourceEvidence  $source
     */
    public function ensureSourceExplained(?StorylineStep $step, array $source, ?string $reason): void
    {
        $contextChanged = $step instanceof StorylineStep && collect($step->source_context)->sortKeys()->all() !== collect($source['context'])->sortKeys()->all();
        if (($source['warnings'] !== [] || $contextChanged) && mb_trim($reason ?? '') === '') {
            throw StorylineConflictException::source();
        }
    }

    /** The event must be dated, already held, and on the same local date as the account. */
    public function ensureEventHeldOn(Event $event, string $occurredOn, string $timezone): void
    {
        if ($event->date === null || $event->date->isFuture() || $occurredOn !== $event->date->copy()->setTimezone($timezone)->toDateString()) {
            throw StorylineConflictException::source();
        }
    }

    /**
     * The cast entries to store: every requested participant must exist, have been involved on the account's date and
     * be a wrestler or tag team. A cast member keeps the label saved for them earlier, so a roster rename never
     * rewrites history.
     *
     * @param  Collection<int, StorylineParticipant>  $participants  the story's participants found for the requested ids, ordered by id
     * @param  list<int>  $participantIds  the requested ids
     * @param  list<array{type: string, id: int, label: string}>|null  $savedCast
     * @return list<array{type: string, id: int, label: string}>
     */
    public function castFor(Collection $participants, array $participantIds, string $occurredOn, ?array $savedCast): array
    {
        if ($participants->count() !== count($participantIds)) {
            throw StorylineConflictException::source();
        }
        $savedLabels = collect($savedCast ?? [])->keyBy(fn (array $person): string => $person['type'].':'.$person['id']);
        $cast = [];
        foreach ($participants as $participant) {
            if ($participant->joined_on->toDateString() > $occurredOn || ($participant->left_on !== null && $participant->left_on->toDateString() < $occurredOn)) {
                throw StorylineConflictException::source();
            }
            $person = $participant->participant;
            if (! $person instanceof Wrestler && ! $person instanceof TagTeam) {
                throw StorylineConflictException::source();
            }
            $cast[] = ['type' => $participant->participant_type, 'id' => $participant->participant_id, 'label' => $savedLabels->get($participant->participant_type.':'.$participant->participant_id)['label'] ?? $person->name];
        }

        return $cast;
    }

    /** A correction stays on the step its account was recorded against. */
    public function ensureStepUnchanged(StorylineDevelopment $development, ?int $developmentId, ?int $stepId): void
    {
        if ($developmentId !== null && $development->storyline_step_id !== $stepId) {
            throw StorylineConflictException::state();
        }
    }
}
