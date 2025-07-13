<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Contract for repositories that manage wrestler relationships.
 *
 * This interface defines the methods required for repositories
 * that handle wrestler-manager relationships.
 */
interface ManagesWrestlerRelations
{
    /**
     * Add a manager to the wrestler.
     *
     * @param  Wrestler  $wrestler  The wrestler to add the manager to
     * @param  Manager  $manager  The manager to add
     * @param  Carbon  $startDate  When the management relationship begins
     */
    public function addManager(Wrestler $wrestler, Manager $manager, Carbon $startDate): void;

    /**
     * Remove a manager from the wrestler.
     *
     * @param  Wrestler  $wrestler  The wrestler to remove the manager from
     * @param  Manager  $manager  The manager to remove
     * @param  Carbon  $endDate  When the management relationship ends
     */
    public function removeManager(Wrestler $wrestler, Manager $manager, Carbon $endDate): void;
}
