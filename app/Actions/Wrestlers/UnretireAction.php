<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Unretire a wrestler.
     *
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    public function handle(Wrestler $wrestler, ?Carbon $unretiredDate = null): void
    {
        throw_if(! $wrestler->isRetired(), CannotBeUnretiredException::class, $wrestler.' is not retired so cannot be unretired.');

        $unretiredDate ??= now();

        $this->wrestlerRepository->unretire($wrestler, $unretiredDate);

        EmployAction::run($wrestler, $unretiredDate);
    }
}
