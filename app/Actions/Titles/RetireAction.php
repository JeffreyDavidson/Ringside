<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\CannotBeRetiredException;
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
     *
     * @throws \App\Exceptions\CannotBeRetiredException
     */
    public function handle(Title $title, ?Carbon $retirementDate = null): void
    {
        throw_if($title->isRetired(), CannotBeRetiredException::class, $title.' is already retired.');

        $retirementDate ??= now();

        DeactivateAction::run($title, $retirementDate);

        $this->titleRepository->retire($title, $retirementDate);
    }
}
