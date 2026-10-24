<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Exceptions\Roster\Stables\CannotBeCreatedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Lifecycle\Naming\GuardedNameRequirements;
use App\Lifecycle\Roster\Stables\StableNameLock;
use App\Models\Roster\Stables\Stable;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction,
        protected AddStableMembersAction $addStableMembersAction,
        protected StableNameLock $nameLock,
        protected GuardedNameRequirements $nameRequirements,
    ) {}

    /**
     * Create a stable.
     *
     * This handles the complete stable creation workflow:
     * - Rejects a name another active stable of the same promotion already uses, also when a concurrent request takes it
     *   between the check and the insert (the unique index refuses it); a stable without a promotion has
     *   no database-level name guard on MySQL, so it first takes the name lock, before anything else is written
     * - Creates the stable record with name and description
     * - Adds wrestlers, tag teams, and managers as founding members
     * - Establishes the stable with official debut if debut_date provided; an end date is rejected,
     *   because a stable that starts already ended would keep its founding members (DisbandAction ends a stable)
     * - Creates proper membership tracking with join dates
     * - Makes the stable available for storylines and championship opportunities
     *
     * @param  StableData  $stableData  The data transfer object containing stable information
     * @param  int|null  $promotionId  The promotion the stable belongs to: the enforced promotion for a member, the split stable's promotion for a split, null for an administrator without one
     * @return Stable The newly created stable with all members
     *
     * @throws CannotBeCreatedException When another active stable of the promotion already has the name
     */
    public function handle(StableData $stableData, ?int $promotionId): Stable
    {
        return DB::transaction(function () use ($stableData, $promotionId): Stable {
            $name = mb_trim($stableData->name);

            if ($promotionId === null) {
                $this->nameLock->lock($name);
            }

            $this->nameRequirements->ensureAvailable(
                Stable::query()
                    ->withoutGlobalScope(PromotionContextScope::class)
                    ->where('promotion_id', $promotionId),
                'name',
                $name,
                fn (): CannotBeCreatedException => CannotBeCreatedException::nameTaken($name),
            );

            $stable = Stable::query()
                ->make(['name' => $name])
                ->forceFill(['promotion_id' => $promotionId]);

            if ($stableData->end_date instanceof Carbon) {
                throw CannotBeEstablishedException::withEndDate($stable);
            }

            try {
                $stable->save();
            } catch (UniqueConstraintViolationException) {
                // A concurrent request took the name of this promotion after the check above; the unique index refused the insert.
                throw CannotBeCreatedException::nameTaken($name);
            }

            // Members join on the start date, or today when the stable has none.
            $this->addStableMembersAction->handle($stable, $stableData->members, $stableData->start_date ?? now());

            if ($stableData->start_date instanceof Carbon) {
                $this->establishAction->handle(
                    $stable,
                    $stableData->start_date,
                );
            }

            return $stable;
        });
    }
}
