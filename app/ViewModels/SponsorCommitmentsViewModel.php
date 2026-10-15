<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Queries\Events\SponsorCommitmentContextQuery;
use App\Queries\Events\SponsorInstructionQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;

class SponsorCommitmentsViewModel
{
    public function __construct(private readonly SponsorCommitmentContextQuery $context, private readonly SponsorInstructionQuery $instructions) {}

    /** @return LengthAwarePaginator<int, EventSponsorCommitment> */
    public function rows(Event $event, string $filter): LengthAwarePaginator
    {
        Gate::authorize('manageSponsors', $event);
        $query = EventSponsorCommitment::query()->where('event_id', $event->id)
            ->with(['event.promotion', 'event.pack', 'event.liveRun', 'event.matches', 'sponsor']);
        if (in_array($filter, ['pending', 'completed', 'cancelled'], true)) {
            $query->where(function (Builder $query) use ($filter): void {
                $query->where('status', $filter);
                if ($filter === 'pending') {
                    $query->orWhere(fn (Builder $review): Builder => $review->where('status', SponsorCommitmentStatus::Cancelled)->whereNotNull('pack_key'));
                }
            });
        }

        return $query->orderByRaw('CASE WHEN status = ? AND pack_key IS NOT NULL THEN 0 WHEN status = ? THEN 1 ELSE 2 END', [SponsorCommitmentStatus::Cancelled->value, SponsorCommitmentStatus::Pending->value])
            ->orderByRaw('CASE WHEN deadline_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline_at')->orderBy('id')->paginate(20, pageName: 'commitmentsPage');
    }

    public function status(EventSponsorCommitment $commitment): string
    {
        if ($commitment->status === SponsorCommitmentStatus::Completed && $commitment->deadline_at !== null && $commitment->completed_at?->gt($commitment->deadline_at)) {
            return __('sponsors.completed_late');
        }
        if ($commitment->status === SponsorCommitmentStatus::Pending && $commitment->deadline_at?->lt(now())) {
            return __('sponsors.overdue');
        }

        return $commitment->status->label();
    }

    public function eventNeedsReview(EventSponsorCommitment $commitment): bool
    {
        return $commitment->event_context !== $this->context->get($commitment->event);
    }

    public function instructionNeedsReview(EventSponsorCommitment $commitment): bool
    {
        $content = $commitment->event->pack->content ?? [];
        $target = $this->instructions->target($content, $commitment->pack_key);
        if (str_starts_with($commitment->pack_key ?? '', 'segment:') && ($target['after_match_id'] ?? null) !== null
            && ! $commitment->event->matches->contains('id', $target['after_match_id'])) {
            return true;
        }

        return $this->instructions->needsReview($commitment, $content);
    }

    public function liveState(EventSponsorCommitment $commitment): ?string
    {
        $item = collect($commitment->event->liveRun->content['items'] ?? [])->firstWhere('key', $commitment->pack_key);

        return $item === null ? null : Lang::string('sponsors.live_state', ['state' => LiveItemStatus::tryFrom($item['state'])?->label() ?? $item['state']]);
    }
}
