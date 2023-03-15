<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class DeactivateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Deactivate a title.
     */
    public function handle(Title $title, ?Carbon $deactivationDate = null): void
    {
        $this->ensureCanBeDeactivated($title);

        $deactivationDate ??= now();

        $this->titleRepository->deactivate($title, $deactivationDate);
    }

    /**
     * Ensure a title can be deactivated.
     *
     * @throws \App\Exceptions\CannotBeDeactivatedException
     */
    private function ensureCanBeDeactivated(Title $title): void
    {
        if ($title->isUnactivated()) {
            throw CannotBeDeactivatedException::unactivated($title);
        }

        if ($title->isInactive()) {
            throw CannotBeDeactivatedException::inactive($title);
        }

        if ($title->hasFutureActivation()) {
            throw CannotBeDeactivatedException::hasFutureActivation($title);
        }

        if ($title->isRetired()) {
            throw CannotBeDeactivatedException::retired($title);
        }
    }
}
