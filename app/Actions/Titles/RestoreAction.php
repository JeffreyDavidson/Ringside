<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Restore a title.
     *
     * @param  \App\Models\Title  $title
     * @return void
     */
    public function handle(Title $title): void
    {
        $this->titleRepository->restore($title);
    }
}
