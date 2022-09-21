<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Employ a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon|null  $startDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    public function handle(Referee $referee, ?Carbon $startDate = null): void
    {
        throw_if($referee->canBeEmployed(), CannotBeEmployedException::class);

        $startDate ??= now();

        $this->refereeRepository->employ($referee, $startDate);
    }
}
