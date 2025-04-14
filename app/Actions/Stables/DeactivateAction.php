<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Stable;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

final class DeactivateAction extends BaseStableAction
{
    use AsAction;

    /**
     * Deactivate a stable.
     *
     * @throws CannotBeDeactivatedException
     */
    public function handle(Stable $stable, ?Carbon $deactivationDate = null): void
    {
        $this->ensureCanBeDeactivated($stable);

        $deactivationDate ??= now();

        $this->stableRepository->deactivate($stable, $deactivationDate);
        $this->stableRepository->disassemble($stable, $deactivationDate);
    }

    /**
     * Ensure a stable can be deactivated.
     *
     * @throws CannotBeDeactivatedException
     */
    private function ensureCanBeDeactivated(Stable $stable): void
    {
        if ($stable->isUnactivated()) {
            throw CannotBeDeactivatedException::unactivated();
        }

        if ($stable->isDeactivated()) {
            throw CannotBeDeactivatedException::deactivated();
        }

        if ($stable->hasFutureActivation()) {
            throw CannotBeDeactivatedException::hasFutureActivation();
        }

        if ($stable->isRetired()) {
            throw CannotBeDeactivatedException::retired();
        }
    }
}
