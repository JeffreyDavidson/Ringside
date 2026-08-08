<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use Illuminate\Support\Carbon;

/**
 * Deactivate action for titles.
 *
 * This is an alias for PullAction to maintain backward compatibility with tests.
 * Use PullAction for new code.
 */
class DeactivateAction
{
    public function __construct(
        private PullAction $pullAction
    ) {}

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
