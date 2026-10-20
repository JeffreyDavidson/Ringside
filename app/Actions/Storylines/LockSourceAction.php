<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\SourceData;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Lifecycle\Events\EventCardLock;
use App\Services\Storylines\StorylineSourceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LockSourceAction
{
    public function __construct(private readonly StorylineSourceService $sources, private readonly EventCardLock $card) {}

    /** @return array{context: array<string, mixed>, fingerprint: string, warnings: list<string>} */
    public function handle(SourceData $source, int $promotionId, bool $historical = false): array
    {
        if ($source->eventId !== null) {
            $this->card->lockIncludingDeleted($source->eventId, $promotionId);
        }
        $result = $this->sources->get($source, $promotionId) ?? throw new ModelNotFoundException;
        if ($source->fingerprint !== null && ! hash_equals($source->fingerprint, $result['fingerprint'])) {
            throw StorylineConflictException::source();
        }
        if (! $historical && array_diff($result['warnings'], [__('storylines.undated_source'), __('storylines.skipped_source')]) !== []) {
            throw StorylineConflictException::source();
        }

        return $result;
    }
}
