<?php

declare(strict_types=1);

namespace App\Actions\Titles;

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
     */
    public function handle(Title $title, ?Carbon $deactivationDate = null): void
    {
        $deactivationDate ??= now();

        $this->titleRepository->deactivate($title, $deactivationDate);
    }
}
