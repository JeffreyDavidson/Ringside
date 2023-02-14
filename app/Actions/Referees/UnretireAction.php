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
     *
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    public function handle(Referee $referee, ?Carbon $unretiredDate = null): void
    {
        throw_if($referee->canBeUnretired(), CannotBeUnretiredException::class);

        $unretiredDate ??= now();

        $this->refereeRepository->unretire($referee, $unretiredDate);

        EmployAction::run($referee, $unretiredDate);
    }
}
