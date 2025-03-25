<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\CannotBeRetiredException;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Retire a title.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(Title $title, ?Carbon $retirementDate = null): void
    {
        $this->ensureCanBeRetired($title);

        $retirementDate ??= now();

        if ($title->isCurrentlyActivated()) {
            $this->titleRepository->deactivate($title, $retirementDate);
        }

        $this->titleRepository->retire($title, $retirementDate);
    }

    /**
     * Ensure a title can be retired.
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    private function ensureCanBeRetired(Title $title): void
    {
        if ($title->isUnactivated()) {
            throw CannotBeRetiredException::unemployed();
        }

        if ($title->hasFutureActivation()) {
            throw CannotBeRetiredException::hasFutureEmployment();
        }

        if ($title->isRetired()) {
            throw CannotBeRetiredException::retired();
        }
    }
}
