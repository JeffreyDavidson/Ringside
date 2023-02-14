<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Enums\TagTeamStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ClearInjuryAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Clear an injury of a wrestler.
     */
    public function handle(Wrestler $wrestler, ?Carbon $recoveryDate = null): void
    {
        throw_if(! $wrestler->isInjured(), CannotBeClearedFromInjuryException::class, $wrestler->name.' is not currently injured so cannot be cleared from injury.');

        $recoveryDate ??= now();

        $this->wrestlerRepository->clearInjury($wrestler, $recoveryDate);

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            $wrestler->currentTagTeam->save(); // Calls observer save method to check what status tag team should be.
        }

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            // Problem with this line below is because it doesn't know the status of the other wrestler of the tag team.
            $wrestler->currenTagTeam()->update(['status' => TagTeamStatus::BOOKABLE]);
        }
    }
}
