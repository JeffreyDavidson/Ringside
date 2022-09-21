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
     *
     * @param  \App\Models\Title  $title
     * @param  \Illuminate\Support\Carbon|null  $deactivationDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeDeactivatedException
     */
    public function handle(Title $title, ?Carbon $deactivationDate = null): void
    {
        throw_if(! $title->isCurrentlyActivated(), CannotBeDeactivatedException::class, $title.' is not currently active and cannot be deactivated.');

        $deactivationDate ??= now();

        $this->titleRepository->deactivate($title, $deactivationDate);
    }
}
