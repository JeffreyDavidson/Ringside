<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        protected StartActivityPeriodAction $startActivityPeriodAction,
        protected RetirementPeriodManager $retirementPeriods,
    ) {}

    /**
     * Unretire a retired stable and make it active again.
     *
     * This handles the complete stable unretirement workflow with flexible options:
     * - Validates the stable can be unretired (business rule compliance)
     * - Ends the current retirement period with the specified date
     * - Leaves former members' individual retirement state unchanged
     * - Optionally establishes the stable immediately or leaves inactive for manual setup
     * - Flexible member requirements for different unretirement scenarios
     * - Makes the stable available for new storylines and championship opportunities
     * - Re-establishes the stable as an active competitive force
     *
     * @param  Stable  $stable  The stable to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @param  bool  $establishImmediately  Whether to establish the stable immediately (default: true)
     * @param  bool  $requireFormerMembers  Whether to require available former members (default: true)
     * @throws CannotBeUnretiredException When stable cannot be unretired due to business rules
     */
    public function handle(
        Stable $stable,
        ?Carbon $unretiredDate = null,
        bool $establishImmediately = true,
        bool $requireFormerMembers = true
    ): void {
        $unretiredDate = $unretiredDate ?? now();

        DB::transaction(function () use ($stable, $unretiredDate, $establishImmediately, $requireFormerMembers): void {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($stable->getKey());

            $lockedStable->ensureCanBeUnretired($requireFormerMembers);
            $this->retirementPeriods->end($lockedStable, $unretiredDate, LifecycleTransitionType::Unretired);

            $lockedStable->update(['status' => StableStatus::Inactive]);

            if ($establishImmediately) {
                $this->startActivityPeriodAction->handle($lockedStable, $unretiredDate);
            }
        });
    }
}
