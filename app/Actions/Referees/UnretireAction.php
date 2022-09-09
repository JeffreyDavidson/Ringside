<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Unretire a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @param  \Illuminate\Support\Carbon|null  $unretiredDate
     * @return void
     */
    public function handle(Referee $referee, ?Carbon $unretiredDate = null): void
    {
        throw_unless($referee->canBeUnretired(), CannotBeUnretiredException::class);

        $unretiredDate ??= now();

        $this->refereeRepository->unretire($referee, $unretiredDate);

        EmployAction::run($referee, $unretiredDate);
    }
}
