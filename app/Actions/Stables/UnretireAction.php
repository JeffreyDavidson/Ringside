<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Managers\UnretireAction as ManagersUnretireAction;
use App\Actions\TagTeams\UnretireAction as TagTeamsUnretireAction;
use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Models\Stables\Stable;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction
{
    use AsAction;

    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        protected WrestlersUnretireAction $wrestlersUnretireAction,
        protected TagTeamsUnretireAction $tagTeamsUnretireAction,
        protected ManagersUnretireAction $managersUnretireAction,
        protected EstablishAction $establishAction
    ) {}

    /**
     * Unretire a retired stable and make it active again.
     *
     * This handles the complete stable unretirement workflow with flexible options:
     * - Validates the stable can be unretired (business rule compliance)
     * - Ends the current retirement period with the specified date
     * - Optionally unretires available former members for reunion storylines
     * - Optionally establishes the stable immediately or leaves inactive for manual setup
     * - Flexible member requirements for different unretirement scenarios
     * - Makes the stable available for new storylines and championship opportunities
     * - Re-establishes the stable as an active competitive force
     *
     * @param  Stable  $stable  The stable to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @param  bool  $unretireMembers  Whether to unretire available former members (default: true)
     * @param  bool  $establishImmediately  Whether to establish the stable immediately (default: true)
     * @param  bool  $requireFormerMembers  Whether to require available former members (default: true)
     * @throws CannotBeUnretiredException When stable cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire stable immediately
     * $retiredStable = Stable::where('name', 'Evolution')->first();
     * UnretireAction::run($retiredStable);
     *
     * // Unretire with specific date
     * UnretireAction::run($retiredStable, Carbon::parse('2024-01-01'));
     *
     * // Unretire without establishing immediately (manual activation later)
     * UnretireAction::run($retiredStable, establishImmediately: false);
     *
     * // Unretire without requiring former members
     * UnretireAction::run($retiredStable, requireFormerMembers: false);
     *
     * // Unretire without unretiring members (stable only)
     * UnretireAction::run($retiredStable, unretireMembers: false);
     * ```
     */
    public function handle(
        Stable $stable,
        ?Carbon $unretiredDate = null,
        bool $unretireMembers = true,
        bool $establishImmediately = true,
        bool $requireFormerMembers = true
    ): void {
        $stable->ensureCanBeUnretired($requireFormerMembers);

        $unretiredDate = $unretiredDate ?? now();
        $successCount = 0;
        $failureCount = 0;

        DB::transaction(function () use ($stable, $unretiredDate, $unretireMembers, $establishImmediately, &$successCount, &$failureCount): void {
            // End the current retirement record directly
            $currentRetirement = $stable->retirements()
                ->whereNull('ended_at')
                ->first();

            if ($currentRetirement) {
                $currentRetirement->update(['ended_at' => $unretiredDate]);
            }

            // Attempt to unretire available former members
            if ($unretireMembers) {
                $availableFormerMembers = $stable->getAvailableFormerMembers();

                foreach ($availableFormerMembers as $member) {
                    if (! $member->isRetired()) {
                        continue; // Skip members who are not retired
                    }

                    try {
                        if (method_exists($member, 'getMorphClass')) {
                            $morphClass = $member->getMorphClass();
                            if ($morphClass === 'wrestler') {
                                $this->wrestlersUnretireAction->handle($member, $unretiredDate);
                                $successCount++;
                            } elseif ($morphClass === 'tag_team') {
                                $this->tagTeamsUnretireAction->handle($member, $unretiredDate);
                                $successCount++;
                            } else {
                                throw new InvalidArgumentException("Cannot unretire member: unsupported member type '{$morphClass}' for {$member->name}.");
                            }
                        }
                    } catch (InvalidArgumentException $e) {
                        // Re-throw programming errors - these indicate system issues
                        throw $e;
                    } catch (Exception $e) {
                        $failureCount++;
                        // Log failure for administrative review
                        Log::warning('Member unretirement failed during stable unretirement', [
                            'stable_id' => $stable->id,
                            'stable_name' => $stable->name,
                            'member_id' => $member->id,
                            'member_name' => $member->name,
                            'error' => $e->getMessage(),
                            'unretirement_date' => $unretiredDate->toDateTimeString(),
                        ]);
                    }
                }
            }

            // Update status to inactive (no longer retired, but not active)
            $stable->update(['status' => StableStatus::Inactive]);

            // Establish immediately if requested
            if ($establishImmediately) {
                $this->establishAction->handle($stable, $unretiredDate);
            }
        });

        // Log summary of member unretirement results
        if ($unretireMembers && ($successCount > 0 || $failureCount > 0)) {
            Log::info('Stable unretirement completed', [
                'stable_id' => $stable->id,
                'stable_name' => $stable->name,
                'members_unretired' => $successCount,
                'members_failed' => $failureCount,
                'unretirement_date' => $unretiredDate->toDateTimeString(),
            ]);
        }
    }
}
