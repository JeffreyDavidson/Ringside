<?php

declare(strict_types=1);

namespace App\Services\Titles;

use App\Lifecycle\ChampionshipReignManager;
use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\TitleDeletionEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TitleDeletionService
{
    public function __construct(
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly DeletionStateManager $deletionState,
        private readonly TitleDeletionEligibility $eligibility,
    ) {}

    public function delete(Title $title, Carbon $deletionDate): void
    {
        DB::transaction(function () use ($title, $deletionDate): void {
            $lockedTitle = Title::query()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTitle->isCurrentlyActive()) {
                $lockedTitle->activityPeriods()->whereNull('ended_at')->update(['ended_at' => $deletionDate]);
            } elseif ($lockedTitle->isRetired()) {
                $lockedTitle->retirements()->whereNull('ended_at')->update(['ended_at' => $deletionDate]);
            }

            $this->championshipReigns->endCurrentReign($lockedTitle, $deletionDate);
            $this->deletionState->delete($lockedTitle, $deletionDate);
        });
    }

    public function restore(Title $title, Carbon $restoreDate): void
    {
        DB::transaction(function () use ($title, $restoreDate): void {
            $lockedTitle = Title::query()
                ->withTrashed()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRestore($lockedTitle);
            $this->deletionState->restore($lockedTitle, $restoreDate);
        });
    }
}
