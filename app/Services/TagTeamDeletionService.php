<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\TagTeams\EndCurrentRelationshipsAction;
use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\TagTeamDeletionEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TagTeamDeletionService
{
    public function __construct(
        private readonly EndCurrentRelationshipsAction $endCurrentRelationships,
        private readonly DeletionStateManager $deletionState,
        private readonly TagTeamDeletionEligibility $eligibility,
    ) {}

    public function delete(TagTeam $tagTeam, Carbon $deletionDate): void
    {
        DB::transaction(function () use ($tagTeam, $deletionDate): void {
            $lockedTagTeam = TagTeam::query()
                ->withTrashed()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanDelete($lockedTagTeam);
            $this->endCurrentRelationships->handle($lockedTagTeam, $deletionDate);
            $this->deletionState->delete($lockedTagTeam, $deletionDate);
        });

        $tagTeam->setAttribute($tagTeam->getDeletedAtColumn(), $deletionDate);
    }

    public function restore(TagTeam $tagTeam, Carbon $restoreDate): void
    {
        DB::transaction(function () use ($tagTeam, $restoreDate): void {
            $lockedTagTeam = TagTeam::query()
                ->withTrashed()
                ->whereKey($tagTeam->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRestore($lockedTagTeam);
            $this->deletionState->restore($lockedTagTeam, $restoreDate);
        });

        $tagTeam->setAttribute($tagTeam->getDeletedAtColumn(), null);
    }
}
