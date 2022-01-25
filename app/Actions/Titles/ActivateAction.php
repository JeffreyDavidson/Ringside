<?php

namespace App\Actions\Titles;

use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class ActivateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Activate a title.
     *
     * @param  \App\Models\Title  $title
     *
     * @return void
     */
    public function handle(Title $title): void
    {
        $activationDate = now();

        $this->titleRepository->activate($title, $activationDate);
        $title->save();
    }
}
