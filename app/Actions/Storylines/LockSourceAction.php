<?php

declare(strict_types=1);

namespace App\Actions\Storylines;

use App\Data\Storylines\SourceData;
use App\Enums\Storylines\StorylineSourceWarning;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Lifecycle\Events\EventCardLock;
use App\Services\Storylines\StorylineSourceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LockSourceAction
{
    public function __construct(private readonly StorylineSourceService $sources, private readonly EventCardLock $card) {}

    /** @return array{context: array<string, mixed>, fingerprint: string, warnings: list<StorylineSourceWarning>} */
    public function handle(SourceData $source, int $promotionId, bool $historical = false): array
    {
        if ($source->eventId !== null) {
            $this->card->lockIncludingDeleted($source->eventId, $promotionId);
        }

        $result = $this->sources->get($source, $promotionId) ?? throw new ModelNotFoundException;

        if ($source->fingerprint !== null && ! hash_equals($source->fingerprint, $result['fingerprint'])) {
            throw StorylineConflictException::source();
        }

        if (! $historical && collect($result['warnings'])->contains(fn (StorylineSourceWarning $warning): bool => ! $warning->allowsCurrentSave())) {
            throw StorylineConflictException::source();
        }

        return $result;
    }
}
