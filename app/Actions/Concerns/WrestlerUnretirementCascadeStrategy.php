<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Actions\Wrestlers\EmployAction;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Cascade strategy for wrestler unretirement with employment options.
 *
 * This strategy handles the employment aspect of unretirement, allowing
 * flexible control over whether the wrestler is employed immediately
 * after coming out of retirement.
 */
class WrestlerUnretirementCascadeStrategy
{
    /**
     * Create a cascade strategy that employs the wrestler immediately after unretirement.
     *
     * @return callable Strategy function that employs the wrestler
     */
    public static function withEmployment(): callable
    {
        return function (Wrestler $wrestler, Carbon $date, string $transition): void {
            $employAction = app(EmployAction::class);
            $employAction->handle($wrestler, $date);
        };
    }

    /**
     * Create a cascade strategy that leaves the wrestler unemployed after unretirement.
     *
     * This is useful when you want manual control over employment timing
     * or when the wrestler should remain unemployed for storyline purposes.
     *
     * @return callable Strategy function that does nothing (no employment)
     */
    public static function withoutEmployment(): callable
    {
        return function (Wrestler $wrestler, Carbon $date, string $transition): void {
            // No action needed - wrestler remains unemployed after unretirement
        };
    }
}
