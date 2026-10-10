<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\EmployCurrentManagersAction;
use App\Data\TagTeams\TagTeamData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Roster\TagTeams\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected SynchronizeMembershipAction $synchronizeMembershipAction,
        protected EmployAction $employAction,
        protected EmployCurrentWrestlersAction $employCurrentWrestlersAction,
        protected EmployCurrentManagersAction $employCurrentManagersAction,
        protected RecordNameLock $nameLock,
    ) {}

    /**
     * Update a tag team while preserving its relationship history.
     *
     * Rejects a name or signature move another tag team of the promotion already uses (deleted ones count, as
     * they do for the form rule). Nothing in the database keeps them unique, so it first takes the name locks,
     * before the tag team's own row lock, and the check that follows sees the other transaction's committed tag team.
     *
     * @throws NameTakenException When another tag team of the promotion has the name or the signature move
     */
    public function handle(TagTeam $tagTeam, TagTeamData $tagTeamData): TagTeam
    {
        return DB::transaction(function () use ($tagTeam, $tagTeamData): TagTeam {
            $name = mb_trim($tagTeamData->name);

            $this->nameLock->lock(GuardedName::TagTeamName, $tagTeam->promotion_id, $name);

            if ($tagTeamData->signature_move !== null) {
                $this->nameLock->lock(GuardedName::TagTeamSignatureMove, $tagTeam->promotion_id, $tagTeamData->signature_move);
            }

            $lockedTagTeam = $tagTeam->refreshForUpdate();

            $this->ensureNameIsAvailable($lockedTagTeam, $name, $tagTeamData->signature_move);

            $lockedTagTeam->update([
                'name' => $name,
                'signature_move' => $tagTeamData->signature_move,
            ]);

            $updateDate = now();

            // Handle partnership changes through membership service using membership data
            $membershipData = $tagTeamData->getMembershipData();

            $this->synchronizeMembershipAction->handle(
                $lockedTagTeam,
                $membershipData,
                $updateDate,
            );

            if ($tagTeamData->employment_date instanceof Carbon) {
                if ($lockedTagTeam->currentEmployment()->exists()) {
                    $this->employCurrentWrestlersAction->handle($lockedTagTeam, $tagTeamData->employment_date);
                    $this->employCurrentManagersAction->handle($lockedTagTeam, $tagTeamData->employment_date);
                } elseif (! $lockedTagTeam->employments()->exists()) {
                    // A released, retired or future-employed team keeps its history; only a never-employed team is employed here.
                    $this->employAction->handle($lockedTagTeam, $tagTeamData->employment_date);
                }
            }

            return $lockedTagTeam;
        });
    }

    private function ensureNameIsAvailable(TagTeam $tagTeam, string $name, ?string $signatureMove): void
    {
        $others = TagTeam::query()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->withTrashed()
            ->where('promotion_id', $tagTeam->promotion_id)
            ->whereKeyNot($tagTeam->getKey());

        if ((clone $others)->whereName($name)->exists()) {
            throw NameTakenException::name($name);
        }

        if ($signatureMove !== null && $others->where('signature_move', $signatureMove)->exists()) {
            throw NameTakenException::signatureMove($signatureMove);
        }
    }
}
