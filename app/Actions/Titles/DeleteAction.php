<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Title;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Delete a title.
     */
    public function handle(Title $title): void
    {
        $this->titleRepository->delete($title);
    }
}
