<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeSuspendedException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Suspend a referee.
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    public function handle(Referee $referee, ?Carbon $suspensionDate = null): void
    {
        throw_if($referee->isUnemployed(), CannotBeSuspendedException::class, $referee.' is unemployed and cannot be suspended.');
        throw_if($referee->isReleased(), CannotBeSuspendedException::class, $referee.' is released and cannot be suspended.');
        throw_if($referee->isRetired(), CannotBeSuspendedException::class, $referee.' is retired and cannot be suspended.');
        throw_if($referee->hasFutureEmployment(), CannotBeSuspendedException::class, $referee.' has not been officially employed and cannot be suspended.');
        throw_if($referee->isSuspended(), CannotBeSuspendedException::class, $referee.' is already suspended.');
        throw_if($referee->isInjured(), CannotBeSuspendedException::class, $referee.' is injured and cannot be suspended.');

        $suspensionDate ??= now();

        $this->refereeRepository->suspend($referee, $suspensionDate);
    }
}
