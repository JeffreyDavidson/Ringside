<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Employ a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon|null  $startDate
     * @return void
     */
    public function handle(Wrestler $wrestler, ?Carbon $startDate = null): void
    {
        throw_unless($wrestler->canBeEmployed(), CannotBeEmployedException::class);

        $startDate ??= now();

        $this->wrestlerRepository->employ($wrestler, $startDate);
    }
}
