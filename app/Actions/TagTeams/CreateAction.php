<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Data\TagTeams\TagTeamData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Roster\TagTeams\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Scopes\PromotionContextScope;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Create a new create action instance.
     */
    public function __construct(
        protected EstablishMembershipAction $establishMembershipAction,
        protected EmployAction $employAction,
        protected RecordNameLock $nameLock,
        protected PromotionContextService $promotionContext,
    ) {}

    /**
     * Create a tag team and establish its initial relationships.
     *
     * Rejects a name or signature move another tag team of the promotion already uses (deleted ones count, as
     * they do for the form rule). Nothing in the database keeps them unique, so it first takes the name locks,
     * before anything else is written, and the check that follows sees the other transaction's committed tag team.
     *
     * @throws NameTakenException When another tag team of the promotion has the name or the signature move
     */
    public function handle(TagTeamData $tagTeamData): TagTeam
    {
        return DB::transaction(function () use ($tagTeamData): TagTeam {
            $name = mb_trim($tagTeamData->name);
            $promotionId = $this->promotionContext->isEnforced() ? $this->promotionContext->current()?->id : null;

            $this->nameLock->lock(GuardedName::TagTeamName, $promotionId, $name);

            if ($tagTeamData->signature_move !== null) {
                $this->nameLock->lock(GuardedName::TagTeamSignatureMove, $promotionId, $tagTeamData->signature_move);
            }

            $this->ensureNameIsAvailable($name, $tagTeamData->signature_move, $promotionId);

            // Create the base tag team record
            $tagTeam = TagTeam::query()->create([
                'name' => $name,
                'signature_move' => $tagTeamData->signature_move,
            ]);

            // Get membership data
            $membershipData = $tagTeamData->getMembershipData();

            $this->establishMembershipAction->handle(
                $tagTeam,
                $membershipData,
                $tagTeamData->getJoinDate(),
            );

            if ($tagTeamData->employment_date instanceof Carbon) {
                $this->employAction->handle($tagTeam, $tagTeamData->employment_date);
            }

            return $tagTeam;
        });
    }

    private function ensureNameIsAvailable(string $name, ?string $signatureMove, ?int $promotionId): void
    {
        $tagTeams = TagTeam::query()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->withTrashed();

        if ((clone $tagTeams)->whereNameInPromotion($name, $promotionId)->exists()) {
            throw NameTakenException::name($name);
        }

        if ($signatureMove !== null && $tagTeams->where('signature_move', $signatureMove)->where('promotion_id', $promotionId)->exists()) {
            throw NameTakenException::signatureMove($signatureMove);
        }
    }
}
