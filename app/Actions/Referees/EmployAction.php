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
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    public function handle(Referee $referee, ?Carbon $startDate = null): void
    {
        throw_if($referee->isCurrentlyEmployed(), CannotBeEmployedException::class, $referee.' is currently employed and cannot be employed further.');

        $startDate ??= now();

        $this->refereeRepository->employ($referee, $startDate);
    }
}
