<?php

declare(strict_types=1);

namespace App\Queries\Storylines;

use App\Data\Storylines\SourceData;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SourceQuery
{
    /** @return array{context: array<string, mixed>, fingerprint: string, warnings: list<string>} */
    public function get(SourceData $source, int $promotionId, ?Event $loadedEvent = null, ?EventMatch $loadedMatch = null): array
    {
        Validator::make(['event' => $source->eventId, 'match' => $source->matchId, 'segment' => $source->segmentId, 'target' => $source->targetOn], [
            'event' => ['nullable', 'integer', 'required_with:match,segment'],
            'match' => ['nullable', 'integer', Rule::prohibitedIf($source->segmentId !== null)],
            'segment' => ['nullable', 'uuid', Rule::requiredIf($source->segmentId !== null)], 'target' => ['nullable', 'date_format:Y-m-d', Rule::requiredIf($source->targetOn !== null), Rule::prohibitedIf($source->eventId !== null)],
        ])->validate();
        if ($source->eventId === null) {
            return ['context' => [], 'fingerprint' => hash('sha256', (string) $source->targetOn), 'warnings' => []];
        }
        $event = $loadedEvent ?? Event::query()->withTrashed()->where('promotion_id', $promotionId)
            ->with(['promotion', 'pack', 'liveRun'])->findOrFail($source->eventId);
        $context = ['event_id' => $event->id, 'event_name' => $event->name, 'event_date' => $event->date?->toIso8601String(),
            'event_deleted' => $event->trashed(), 'event_timezone' => $event->promotion?->timezone];
        $warnings = [];
        if ($event->trashed()) {
            $warnings[] = __('storylines.deleted_source');
        }
        if ($event->date === null) {
            $warnings[] = __('storylines.undated_source');
        }
        $key = null;
        if ($source->matchId !== null) {
            $match = $loadedMatch ?? EventMatch::query()->withTrashed()->where('event_id', $event->id)->with(['competitors', 'titles'])->findOrFail($source->matchId);
            $context['match_id'] = $match->id;
            $context['match_label'] = __('storylines.match_number', ['number' => $match->match_number]);
            $context['match_result'] = $match->match_finish?->value;
            $context['winning_side_id'] = $match->winning_side_id;
            $context['match_type'] = $match->match_type->value;
            $context['stipulation_id'] = $match->match_stipulation_id;
            $context['title_ids'] = $match->titles->sortBy('id')->modelKeys();
            $context['match_deleted'] = $match->trashed();
            $context['competitors'] = $match->competitors->sortBy('id')->map(fn (MatchCompetitor $competitor): array => [$competitor->competitor_type, $competitor->competitor_id, $competitor->match_side_id])->values()->all();
            if ($match->trashed()) {
                $warnings[] = __('storylines.deleted_source');
            }
            $key = "match:{$match->id}";
        }
        if ($source->segmentId !== null) {
            $segment = collect($event->pack?->content['segments'] ?? [])->firstWhere('id', $source->segmentId);
            $key = "segment:{$source->segmentId}";
            $context['segment_id'] = $source->segmentId;
            $context['segment_label'] = $segment['label'] ?? null;
            $context['segment_anchor'] = $segment['after_match_id'] ?? null;
            if ($segment === null) {
                $warnings[] = __('storylines.removed_segment');
            }
            if ($segment !== null && $segment['after_match_id'] !== null && ! ($event->relationLoaded('matches') ? $event->matches->contains('id', $segment['after_match_id']) : $event->matches()->whereKey($segment['after_match_id'])->exists())) {
                $warnings[] = __('storylines.unplaced_segment');
            }
        }
        $item = $key === null ? null : collect($event->liveRun?->content['items'] ?? [])->firstWhere('key', $key);
        $context['live_state'] = $item['state'] ?? null;
        $context['live_label'] = $item['label'] ?? null;
        if (($item['state'] ?? null) === 'skipped') {
            $warnings[] = __('storylines.skipped_source');
        }

        return ['context' => $context, 'fingerprint' => hash('sha256', json_encode($context, JSON_THROW_ON_ERROR)), 'warnings' => array_values(array_unique($warnings))];
    }
}
