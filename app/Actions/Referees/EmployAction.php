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
     */
    public function handle(Referee $referee, ?Carbon $startDate = null): void
    {
        $this->ensureCanBeEmployed($referee);

        $startDate ??= now();

        $this->refereeRepository->employ($referee, $startDate);
    }

    /**
     * Ensure a referee can be employed.
     *
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    private function ensureCanBeEmployed(Referee $referee): void
    {
        if ($referee->isCurrentlyEmployed()) {
            throw CannotBeEmployedException::employed($referee);
        }
    }
}
