<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ActivateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Activate a title.
     *
     * @param  \App\Models\Title  $title
     * @param  \Illuminate\Support\Carbon|null  $activationDate
     * @return void
     */
    public function handle(Title $title, ?Carbon $activationDate = null): void
    {
        $activationDate ??= now();

        $this->titleRepository->activate($title, $activationDate);
    }
}
