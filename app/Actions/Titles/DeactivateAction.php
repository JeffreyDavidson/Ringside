<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Deactivate action for titles.
 *
 * This is an alias for PullAction to maintain backward compatibility with tests.
 * Use PullAction for new code.
 */
class DeactivateAction extends BaseTitleAction
{
    use AsAction;

    public function __construct(
        private PullAction $pullAction
    ) {
        parent::__construct($this->pullAction->titleRepository);
    }

    /**
     * Deactivate a title.
     *
     * @param  Title  $title  The title to deactivate
     * @param  Carbon|null  $deactivationDate  The deactivation date (defaults to now)
     */
    public function handle(Title $title, ?Carbon $deactivationDate = null): void
    {
        $this->pullAction->handle($title, $deactivationDate);
    }
}
