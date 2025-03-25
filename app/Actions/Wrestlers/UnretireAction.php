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
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    public function handle(Wrestler $wrestler, ?Carbon $unretiredDate = null): void
    {
        $this->ensureCanBeUnretired($wrestler);

        $unretiredDate ??= now();

        $this->wrestlerRepository->unretire($wrestler, $unretiredDate);
        $this->wrestlerRepository->employ($wrestler, $unretiredDate);
    }

    /**
     * Ensure a wrestler can be unretired.
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    private function ensureCanBeUnretired(Wrestler $wrestler): void
    {
        if (! $wrestler->isRetired()) {
            throw CannotBeUnretiredException::notRetired();
        }
    }
}
