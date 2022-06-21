<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Unretire a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @param  \Illuminate\Support\Carbon|null  $unretiredDate
     * @return void
     */
    public function handle(Wrestler $wrestler, ?Carbon $unretiredDate = null): void
    {
        $unretiredDate ??= now();

        $this->wrestlerRepository->unretire($wrestler, $unretiredDate);
        $this->wrestlerRepository->employ($wrestler, $unretiredDate);
    }
}
