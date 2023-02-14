<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Exceptions\CannotBeInjuredException;
use App\Models\Referee;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class InjureAction extends BaseRefereeAction
{
    use AsAction;

    /**
     * Injure a referee.
     *
     *
     * @throws \App\Exceptions\CannotBeInjuredException
     */
    public function handle(Referee $referee, ?Carbon $injureDate = null): void
    {
        throw_if($referee->isInjured(), CannotBeInjuredException::class, $referee.' is currently injured and cannot be injured further.');

        $injureDate ??= now();

        $this->refereeRepository->injure($referee, $injureDate);
    }
}
