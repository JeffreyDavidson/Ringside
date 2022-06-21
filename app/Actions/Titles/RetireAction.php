<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Retire a title.
     *
     * @param  \App\Models\Title  $title
     * @param  \Illuminate\Support\Carbon|null  $retirementDate
     * @return void
     */
    public function handle(Title $title, ?Carbon $retirementDate = null): void
    {
        $retirementDate ??= now();

        $this->titleRepository->deactivate($title, $retirementDate);
        $this->titleRepository->retire($title, $retirementDate);
    }
}
