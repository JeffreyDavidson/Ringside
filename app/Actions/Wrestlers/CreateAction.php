<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\AssignManagersAction;
use App\Data\Wrestlers\WrestlerData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Roster\Wrestlers\NameTakenException;
use App\Lifecycle\Naming\GuardedNameRequirements;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function __construct(
        protected EmployAction $employAction,
        protected AssignManagersAction $assignManagersAction,
        protected RecordNameLock $nameLock,
        protected GuardedNameRequirements $nameRequirements,
    ) {}

    /**
     * Create a wrestler.
     *
     * Rejects a name or signature move another wrestler of the promotion already uses (deleted ones count, as
     * they do for the form rule). Nothing in the database keeps them unique, so it first takes the name locks,
     * before anything else is written, and the check that follows sees the other transaction's committed wrestler.
     *
     * @param  int|null  $promotionId  The promotion the wrestler belongs to: the enforced promotion for a member, null for an administrator without one
     *
     * @throws NameTakenException When another wrestler of the promotion has the name or the signature move
     */
    public function handle(WrestlerData $wrestlerData, ?int $promotionId): Wrestler
    {
        return DB::transaction(function () use ($wrestlerData, $promotionId): Wrestler {
            $name = mb_trim($wrestlerData->name);

            $this->nameLock->lock(GuardedName::WrestlerName, $promotionId, $name);

            if ($wrestlerData->signature_move !== null) {
                $this->nameLock->lock(GuardedName::WrestlerSignatureMove, $promotionId, $wrestlerData->signature_move);
            }

            $this->ensureNameIsAvailable($name, $wrestlerData->signature_move, $promotionId);

            $wrestler = Wrestler::query()
                ->make([
                    'name' => $name,
                    'height' => $wrestlerData->height,
                    'weight' => $wrestlerData->weight,
                    'hometown' => $wrestlerData->hometown,
                    'signature_move' => $wrestlerData->signature_move,
                ])
                ->forceFill(['promotion_id' => $promotionId]);
            $wrestler->save();

            if ($wrestlerData->managers instanceof Collection && $wrestlerData->managers->isNotEmpty()) {
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
            ->withTrashed()
            ->where('promotion_id', $promotionId);

        $this->nameRequirements->ensureAvailable($wrestlers, 'name', $name, fn (): NameTakenException => NameTakenException::name($name));

        if ($signatureMove !== null) {
            $this->nameRequirements->ensureAvailable(
                $wrestlers,
                'signature_move',
                $signatureMove,
                fn (): NameTakenException => NameTakenException::signatureMove($signatureMove),
            );
        }
    }
}
