<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Models\Matches\EventMatch;
use App\Services\Matches\EventMatchDeletionService;
use Illuminate\Support\Carbon;

class DeleteAction
{
    public function __construct(private readonly EventMatchDeletionService $deletion) {}

    public function handle(EventMatch $eventMatch, ?Carbon $deletedAt = null): void
    {
        $this->deletion->delete($eventMatch, $deletedAt ?? now());
    }
}
