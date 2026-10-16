<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\SourceData;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Queries\Storylines\StorylineSourceQuery;

class LockSourceAction
{
    public function __construct(private readonly StorylineSourceQuery $query) {}

    /** @return array{context: array<string, mixed>, fingerprint: string, warnings: list<string>} */
    public function handle(SourceData $source, int $promotionId, bool $historical = false): array
    {
        if ($source->eventId !== null) {
            Event::query()->withTrashed()->where('promotion_id', $promotionId)->whereKey($source->eventId)->lockForUpdate()->firstOrFail();
            EventMatch::query()->withTrashed()->where('event_id', $source->eventId)->orderBy('id')->lockForUpdate()->get();
            EventPack::query()->where('event_id', $source->eventId)->lockForUpdate()->first();
            EventLiveRun::query()->where('event_id', $source->eventId)->lockForUpdate()->first();
        }
        $result = $this->query->get($source, $promotionId);
        if ($source->fingerprint !== null && ! hash_equals($source->fingerprint, $result['fingerprint'])) {
            throw StorylineConflictException::source();
        }
        if (! $historical && array_diff($result['warnings'], [__('storylines.undated_source'), __('storylines.skipped_source')]) !== []) {
            throw StorylineConflictException::source();
        }

        return $result;
    }
}
