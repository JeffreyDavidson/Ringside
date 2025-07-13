<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Activate action for titles.
 *
 * This handles both unretiring and debuting titles to make them active.
 * Use DebutAction for new code that only needs to debut non-retired titles.
 */
class ActivateAction extends BaseTitleAction
{
    use AsAction;

    public function __construct(
        private DebutAction $debutAction,
        private ReinstateAction $reinstateAction,
        private UnretireAction $unretireAction
    ) {
        parent::__construct($this->debutAction->titleRepository);
    }

    /**
     * Activate a title.
     *
     * @param  Title  $title  The title to activate
     * @param  Carbon|null  $activationDate  The activation date (defaults to now)
     */
    public function handle(Title $title, ?Carbon $activationDate = null): void
    {
        $activationDate = $activationDate ?? now();

        // If the title is retired, first unretire it
        if ($title->isRetired()) {
            $this->unretireAction->handle($title, $activationDate);
        }

        // Determine if this is a debut or reinstatement
        if ($title->hasActivityPeriods()) {
            // Title has been debuted before, so reinstate it
            $this->reinstateAction->handle($title, $activationDate);
        } else {
            // Title has never been debuted, so debut it
            $this->debutAction->handle($title, $activationDate);
        }
    }
}
