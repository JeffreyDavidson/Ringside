<?php

namespace App\Actions\Titles;

use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class DeactivateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Deactivate a title.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function handle(Title $title): void
    {
        $deactivationDate = now()->toDateTimeString();

        $this->titleRepository->deactivate($title, $deactivationDate);
        $title->updateStatus()->save();
    }
}
