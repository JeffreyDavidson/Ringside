<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\AssignManagersAction;
use App\Data\Wrestlers\WrestlerData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Roster\Wrestlers\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function __construct(
        protected EmployAction $employAction,
        protected AssignManagersAction $assignManagersAction,
        protected RecordNameLock $nameLock,
        protected PromotionContextService $promotionContext,
    ) {}

    /**
     * Create a wrestler.
     *
     * Rejects a name or signature move another wrestler of the promotion already uses (deleted ones count, as
     * they do for the form rule). Nothing in the database keeps them unique, so it first takes the name locks,
     * before anything else is written, and the check that follows sees the other transaction's committed wrestler.
     *
     * @throws NameTakenException When another wrestler of the promotion has the name or the signature move
     */
    public function handle(WrestlerData $wrestlerData): Wrestler
    {
        return DB::transaction(function () use ($wrestlerData): Wrestler {
            $name = mb_trim($wrestlerData->name);
            $promotionId = $this->promotionContext->isEnforced() ? $this->promotionContext->current()?->id : null;

            $this->nameLock->lock(GuardedName::WrestlerName, $promotionId, $name);

            if ($wrestlerData->signature_move !== null) {
                $this->nameLock->lock(GuardedName::WrestlerSignatureMove, $promotionId, $wrestlerData->signature_move);
            }

            $this->ensureNameIsAvailable($name, $wrestlerData->signature_move, $promotionId);

            $wrestler = Wrestler::query()->create([
                'name' => $name,
                'height' => $wrestlerData->height,
                'weight' => $wrestlerData->weight,
                'hometown' => $wrestlerData->hometown,
                'signature_move' => $wrestlerData->signature_move,
            ]);

            if ($wrestlerData->hasManagers()) {
                $this->assignManagersAction->handle(
                    $wrestler,
                    $wrestlerData->managers,
                    $wrestlerData->employment_date ?? now(),
                );
            }

            if (isset($wrestlerData->employment_date)) {
                $this->employAction->handle($wrestler, $wrestlerData->employment_date);
            }

            return $wrestler;
        });
    }

    private function ensureNameIsAvailable(string $name, ?string $signatureMove, ?int $promotionId): void
    {
        $wrestlers = Wrestler::query()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->withTrashed();

        if ((clone $wrestlers)->whereNameInPromotion($name, $promotionId)->exists()) {
            throw NameTakenException::name($name);
        }

        if ($signatureMove !== null && $wrestlers->where('signature_move', $signatureMove)->where('promotion_id', $promotionId)->exists()) {
            throw NameTakenException::signatureMove($signatureMove);
        }
    }
}
