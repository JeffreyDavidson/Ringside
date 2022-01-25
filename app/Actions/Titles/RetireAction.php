<?php

namespace App\Actions\Titles;

use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Retire a title.
     *
     * @param  \App\Models\Title  $title
     *
     * @return void
     */
    public function handle(Title $title): void
    {
        $retirementDate = now();

        $this->titleRepository->deactivate($title, $retirementDate);
        $this->titleRepository->retire($title, $retirementDate);
        $title->save();
    }
}
