<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualRestoreService;
use Illuminate\Support\Carbon;

class RestoreAction
{
    public function __construct(
        private readonly IndividualRestoreService $restore,
    ) {}

    /**
     * Restore a soft-deleted wrestler record.
     *
     * This action only restores the wrestler record itself. All relationships
     * (employment, tag teams, stables, managers) must be re-established separately
     * using appropriate actions to avoid conflicts and provide explicit control.
     *
     * Use cases after restoration:
     * - EmployAction: To re-employ the wrestler
     * - TagTeam actions: To rejoin tag teams if appropriate
     * - Stable actions: To rejoin stables if appropriate
     * - Manager relationships: To re-establish management if appropriate
     */
    public function handle(Wrestler $wrestler, ?Carbon $restoreDate = null): void
    {
        $restoreDate = $restoreDate ?? now();

        $this->restore->restore($wrestler, $restoreDate);
    }
}
