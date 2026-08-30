<?php

declare(strict_types=1);

namespace App\Services\Titles;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Lifecycle\Titles\TitleDeletionEligibility;
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
            $lockedTitle = $title->refreshForUpdate();

            if ($lockedTitle->currentActivityPeriod()->exists()) {
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
            $lockedTitle = $title->refreshForUpdate();

            $this->eligibility->ensureCanRestore($lockedTitle);
            $this->deletionState->restore($lockedTitle, $restoreDate);
        });
    }
}
