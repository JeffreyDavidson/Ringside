<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

final class RestoreAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Restore a title.
     */
    public function handle(Title $title): void
    {
        $this->titleRepository->restore($title);
    }
}
