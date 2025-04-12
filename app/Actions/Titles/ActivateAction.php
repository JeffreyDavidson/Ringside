<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\CannotBeActivatedException;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

final class ActivateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Activate a title.
     *
     * @throws CannotBeActivatedException
     */
    public function handle(Title $title, ?Carbon $activationDate = null): void
    {
        $this->ensureCanBeActivated($title);

        $activationDate ??= now();

        if ($title->isRetired()) {
            $this->titleRepository->unretire($title, $activationDate);
        }

        $this->titleRepository->activate($title, $activationDate);
    }

    /**
     * Ensure a title can be activated.
     *
     * @throws CannotBeActivatedException
     */
    private function ensureCanBeActivated(Title $title): void
    {
        if ($title->isCurrentlyActivated()) {
            throw CannotBeActivatedException::activated();
        }
    }
}
