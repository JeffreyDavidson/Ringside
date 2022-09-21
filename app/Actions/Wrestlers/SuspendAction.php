<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Suspend a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon|null  $suspensionDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    public function handle(Wrestler $wrestler, ?Carbon $suspensionDate = null): void
    {
        throw_if($wrestler->isUnemployed(), CannotBeSuspendedException::class, $wrestler.' is unemployed and cannot be suspended.');
        throw_if($wrestler->isReleased(), CannotBeSuspendedException::class, $wrestler.' is released and cannot be suspended.');
        throw_if($wrestler->isRetired(), CannotBeSuspendedException::class, $wrestler.' is retired and cannot be suspended.');
        throw_if($wrestler->hasFutureEmployment(), CannotBeSuspendedException::class, $wrestler.' has not been officially employed and cannot be suspended.');
        throw_if($wrestler->isSuspended(), CannotBeSuspendedException::class, $wrestler.' is already suspended.');
        throw_if($wrestler->isInjured(), CannotBeSuspendedException::class, $wrestler.' is injured and cannot be suspended.');

        $suspensionDate ??= now();

        $this->wrestlerRepository->suspend($wrestler, $suspensionDate);

        if ($wrestler->isAMemberOfCurrentTagTeam()) {
            $wrestler->currentTagTeam->save();
        }
    }
}
