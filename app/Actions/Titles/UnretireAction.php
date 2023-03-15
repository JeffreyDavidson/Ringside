<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\CannotBeUnretiredException;
use App\Models\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Unretire a title.
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    public function handle(Title $title, ?Carbon $unretiredDate = null): void
    {
        $this->ensureCanBeUnretired($title);

        $unretiredDate ??= now();

        $this->titleRepository->unretire($title, $unretiredDate);
        $this->titleRepository->activate($title, $unretiredDate);
    }

    /**
     * Ensure a title can be unretired.
     *
     * @throws \App\Exceptions\CannotBeUnretiredException
     */
    private function ensureCanBeUnretired(Title $title)
    {
        if (! $title->isRetired()) {
            throw CannotBeUnretiredException::notRetired($title);
        }
    }
}
