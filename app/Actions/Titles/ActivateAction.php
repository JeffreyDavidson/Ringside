<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Services\TitleActivationService;
use Illuminate\Support\Carbon;

/**
 * Activate action for titles.
 *
 * This handles both unretiring and debuting titles to make them active.
 * Use DebutAction for new code that only needs to debut non-retired titles.
 */
class ActivateAction
{
    public function __construct(
        private TitleActivationService $activation,
    ) {}

    /**
     * Activate a title.
     *
     * @param  Title  $title  The title to activate
     * @param  Carbon|null  $activationDate  The activation date (defaults to now)
     */
    public function handle(Title $title, ?Carbon $activationDate = null): void
    {
        $this->activation->activate($title, $activationDate ?? now());
    }
}
