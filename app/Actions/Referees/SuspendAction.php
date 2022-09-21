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
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon|null  $suspensionDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeSuspendedException
     */
    public function handle(Referee $referee, ?Carbon $suspensionDate = null): void
    {
        throw_if($referee->canBeSuspended(), CannotBeSuspendedException::class);

        $suspensionDate ??= now();

        $this->refereeRepository->suspend($referee, $suspensionDate);
    }
}
