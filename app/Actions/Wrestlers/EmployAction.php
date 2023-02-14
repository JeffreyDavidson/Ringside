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
     *
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    public function handle(Wrestler $wrestler, ?Carbon $startDate = null): void
    {
        throw_if($wrestler->isCurrentlyEmployed(), CannotBeEmployedException::class, $wrestler.' is already employed.');

        $startDate ??= now();

        $this->wrestlerRepository->employ($wrestler, $startDate);
    }
}
