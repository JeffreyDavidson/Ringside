<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Services\Events\SponsorCommitmentContextService;
use App\Services\Events\SponsorInstructionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Lang;

/**
 * @phpstan-type Detail array{status: string, overdue: bool, eventNeedsReview: bool, instructionNeedsReview: bool, liveState: ?string}
 * @phpstan-type Screen array{rows: LengthAwarePaginator<int, EventSponsorCommitment>, details: array<int, Detail>}
 */
class SponsorCommitmentsViewModel
{
    public function __construct(private readonly SponsorCommitmentContextService $context, private readonly SponsorInstructionService $instructions) {}

    /**
     * One page of the event's promises with what the page shows beside each. The caller authorizes the event first.
     *
     * @return Screen
     */
    public function data(Event $event, string $filter): array
    {
        $query = EventSponsorCommitment::query()->forEventId($event->id)
            ->with(['event.promotion', 'event.pack', 'event.liveRun', 'event.matches', 'sponsor']);
        if (in_array($filter, ['pending', 'completed', 'cancelled'], true)) {
            $query->where(function (Builder $query) use ($filter): void {
                $query->where('status', $filter);
                if ($filter === 'pending') {
                    $query->orWhere(fn (Builder $review): Builder => $review->where('status', SponsorCommitmentStatus::Cancelled)->whereNotNull('pack_key'));
                }
            });
        }

        $rows = $query->orderByRaw('CASE WHEN status = ? AND pack_key IS NOT NULL THEN 0 WHEN status = ? THEN 1 ELSE 2 END', [SponsorCommitmentStatus::Cancelled->value, SponsorCommitmentStatus::Pending->value])
            ->orderByRaw('CASE WHEN deadline_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline_at')->orderBy('id')->paginate(20, pageName: 'commitmentsPage');

        return ['rows' => $rows, 'details' => $this->details($rows)];
    }

    /**
     * The status each promise is shown with, keyed by promise id.
     *
     * @param  iterable<EventSponsorCommitment>  $commitments
     * @return array<int, string>
     */
    public function statuses(iterable $commitments): array
    {
        $statuses = [];
        foreach ($commitments as $commitment) {
            $statuses[$commitment->id] = $this->status($commitment);
        }

        return $statuses;
    }

    /**
     * What an event's promise list shows beside each promise, keyed by promise id. Needs each promise's event, pack,
     * live run and matches loaded.
     *
     * @param  iterable<EventSponsorCommitment>  $commitments
     * @return array<int, Detail>
     */
    public function details(iterable $commitments): array
    {
        $details = [];
        foreach ($commitments as $commitment) {
            $status = $this->status($commitment);
            $details[$commitment->id] = ['status' => $status, 'overdue' => $status === __('sponsors.overdue'),
                'eventNeedsReview' => $this->eventNeedsReview($commitment), 'instructionNeedsReview' => $this->instructionNeedsReview($commitment),
                'liveState' => $this->liveState($commitment)];
        }

        return $details;
    }

    private function status(EventSponsorCommitment $commitment): string
    {
        if ($commitment->status === SponsorCommitmentStatus::Completed && $commitment->deadline_at !== null && $commitment->completed_at?->gt($commitment->deadline_at)) {
            return __('sponsors.completed_late');
        }
        if ($commitment->status === SponsorCommitmentStatus::Pending && $commitment->deadline_at?->lt(now())) {
            return __('sponsors.overdue');
        }

        return $commitment->status->label();
    }

    private function eventNeedsReview(EventSponsorCommitment $commitment): bool
    {
        return $commitment->event_context !== $this->context->get($commitment->event);
    }

    private function instructionNeedsReview(EventSponsorCommitment $commitment): bool
    {
        $content = $commitment->event->pack->content ?? [];
        $target = $this->instructions->target($content, $commitment->pack_key);
        if (str_starts_with($commitment->pack_key ?? '', 'segment:') && ($target['after_match_id'] ?? null) !== null
            && ! $commitment->event->matches->contains('id', $target['after_match_id'])) {
            return true;
        }

        return $this->instructions->needsReview($commitment, $content);
    }

    private function liveState(EventSponsorCommitment $commitment): ?string
    {
        $item = collect($commitment->event->liveRun->content['items'] ?? [])->firstWhere('key', $commitment->pack_key);

        return $item === null ? null : Lang::string('sponsors.live_state', ['state' => LiveItemStatus::tryFrom($item['state'])?->label() ?? $item['state']]);
    }
}
