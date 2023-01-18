<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\CannotBeActivatedException;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ActivateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * @throws \App\Exceptions\CannotBeActivatedException
     */
    public function handle(Title $title, ?Carbon $activationDate = null): void
    {
        throw_if($title->isCurrentlyActivated(), CannotBeActivatedException::class, $title.' is already activated.');

        $activationDate ??= now();

        $this->titleRepository->activate($title, $activationDate);
    }
}
