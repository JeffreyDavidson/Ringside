<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseWrestlerAction
{
    use AsAction;

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
        $restoreDate = $this->getEffectiveDate($restoreDate);

        DB::transaction(function () use ($wrestler): void {
            $this->wrestlerRepository->restore($wrestler);

            // Note: No automatic relationship restoration to avoid conflicts.
            // All employment, tag team, stable, and manager relationships
            // must be re-established explicitly using separate actions.
        });
    }
}
