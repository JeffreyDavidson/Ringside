<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Unretire a referee.
     */
    public function handle(Referee $referee, ?Carbon $unretiredDate = null): void
    {
        $this->ensureCanBeUnretired($referee);

        $unretiredDate ??= now();

        $this->refereeRepository->unretire($referee, $unretiredDate);
        $this->refereeRepository->employ($referee, $unretiredDate);
    }

    /**
     * Ensure a referee can be unretired.
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    private function ensureCanBeUnretired(Referee $referee)
    {
        if (! $referee->isRetired()) {
            throw CannotBeUnretiredException::notRetired($referee);
        }
    }
}
