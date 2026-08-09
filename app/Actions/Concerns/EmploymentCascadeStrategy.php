<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Actions\Managers\EmployAction as EmployManagerAction;
use App\Actions\Wrestlers\EmployAction as EmployWrestlerAction;
use App\Models\Managers\Manager;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Employment cascade strategies for automatic relationship-based employment.
 *
 * This class provides strategies for automatically employing related entities when
 * a primary entity is employed. Common cascading scenarios include employing managers
 * when wrestlers or tag teams are employed.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions require managers to be employed in order to actively manage
 * talent. When a wrestler or tag team gets employed, their managers should also be
 * employed if they aren't already.
 *
 * Each method returns a callable strategy for employment orchestration.
 */
class EmploymentCascadeStrategy
{
    /**
     * Strategy to employ all unemployed managers of the entity.
     *
     * @return callable Strategy function for manager employment cascade
     */
    public static function managers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on employment transitions
            if ($transition !== 'employ') {
                return;
            }

            // Check if entity has manager relationships
            if (! method_exists($entity, 'currentManagers')) {
                return;
            }

            $unemployedManagers = $entity->currentManagers()
                ->get()
                ->filter(fn (Manager $manager) => ! $manager->isEmployed());

            foreach ($unemployedManagers as $manager) {
                resolve(EmployManagerAction::class)->handle($manager, $date);
            }
        };
    }

    /**
     * Strategy to employ all unemployed wrestlers of the entity (for tag teams).
     *
     * @return callable Strategy function for wrestler employment cascade
     */
    public static function wrestlers(): callable
    {
        return function (Model $entity, Carbon $date, string $transition): void {
            // Only cascade on employment transitions
            if ($transition !== 'employ') {
                return;
            }

            // Check if entity has wrestler relationships (tag teams)
            if (! method_exists($entity, 'currentWrestlers')) {
                return;
            }

            $unemployedWrestlers = $entity->currentWrestlers()
                ->get()
                ->filter(fn (Wrestler $wrestler) => ! $wrestler->isEmployed());

            foreach ($unemployedWrestlers as $wrestler) {
                resolve(EmployWrestlerAction::class)->handle($wrestler, $date);
            }
        };
    }
}
