<?php

namespace App\Actions\Titles;

use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Unretire a title.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function handle(Title $title): void
    {
        $unretiredDate = now()->toDateTimeString();

        $this->titleRepository->unretire($title, $unretiredDate);
        $this->titleRepository->activate($title, $unretiredDate);
        $title->updateStatus()->save();
    }
}
