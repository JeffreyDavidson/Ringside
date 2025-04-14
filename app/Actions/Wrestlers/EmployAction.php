<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

final class EmployAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Employ a wrestler.
     *
     * @throws CannotBeEmployedException
     */
    public function handle(Wrestler $wrestler, ?Carbon $startDate = null): void
    {
        $this->ensureCanBeEmployed($wrestler);

        $startDate ??= now();

        if ($wrestler->isRetired()) {
            $this->wrestlerRepository->unretire($wrestler, $startDate);
        }

        $this->wrestlerRepository->employ($wrestler, $startDate);
    }

    /**
     * Ensure a wrestler can be employed.
     *
     * @throws CannotBeEmployedException
     */
    private function ensureCanBeEmployed(Wrestler $wrestler): void
    {
        if ($wrestler->isCurrentlyEmployed()) {
            throw CannotBeEmployedException::employed();
        }
    }
}
