<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Data\Wrestlers\WrestlerData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Roster\Wrestlers\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Support\Facades\DB;

/**
 * Action for updating wrestler information and managing employment status.
 *
 * This action handles the complete workflow for updating a wrestler's information,
 * including automatically creating employment records when appropriate. It ensures
 * data consistency by performing updates and employment operations atomically.
 *
 * The action follows these business rules:
 * - Always updates the wrestler's basic information first
 * - Uses EmployAction for consistent employment handling when employment_date is provided and the wrestler has no employment history
 * - Automatically employs managers through EmployAction's typed collaborator
 * - Maintains employment history through proper action coordination
 * - Rejects a name or signature move another wrestler of the promotion already uses (deleted ones count, as they do for
 *   the form rule); nothing in the database keeps them unique, so it first takes the name locks, before the wrestler's own row lock
 */
class UpdateAction
{
    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected EmployAction $employAction,
        protected RecordNameLock $nameLock,
    ) {}

    /**
     * Update a wrestler's information and handle employment status.
     *
     * This handles the complete update workflow:
     * - Updates wrestler's basic information
     * - Uses EmployAction for consistent employment creation when employment_date provided and the wrestler has never been employed
     * - Automatically employs managers through EmployAction's typed collaborator
     * - Maintains transaction boundaries for data consistency
     *
     * @throws NameTakenException When another wrestler of the promotion has the name or the signature move
     */
    public function handle(Wrestler $wrestler, WrestlerData $wrestlerData): Wrestler
    {
        return DB::transaction(function () use ($wrestler, $wrestlerData): Wrestler {
            $name = mb_trim($wrestlerData->name);

            $this->nameLock->lock(GuardedName::WrestlerName, $wrestler->promotion_id, $name);

            if ($wrestlerData->signature_move !== null) {
                $this->nameLock->lock(GuardedName::WrestlerSignatureMove, $wrestler->promotion_id, $wrestlerData->signature_move);
            }

            $lockedWrestler = $wrestler->refreshForUpdate();

            $this->ensureNameIsAvailable($lockedWrestler, $name, $wrestlerData->signature_move);

            $lockedWrestler->update([
                'name' => $name,
                'height' => $wrestlerData->height,
                'weight' => $wrestlerData->weight,
                'hometown' => $wrestlerData->hometown,
                'signature_move' => $wrestlerData->signature_move,
            ]);

            // Only a wrestler with no employment history is employed from the form date; a released, retired or
            // future-employed wrestler keeps their history (employing again would overlap or be rejected).
            if (! is_null($wrestlerData->employment_date) && ! $lockedWrestler->employments()->exists()) {
                $this->employAction->handle($lockedWrestler, $wrestlerData->employment_date);
            }

            return $lockedWrestler;
        });
    }

    private function ensureNameIsAvailable(Wrestler $wrestler, string $name, ?string $signatureMove): void
    {
        $others = Wrestler::query()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->withTrashed()
            ->where('promotion_id', $wrestler->promotion_id)
            ->whereKeyNot($wrestler->getKey());

        if ((clone $others)->whereName($name)->exists()) {
            throw NameTakenException::name($name);
        }

        if ($signatureMove !== null && $others->where('signature_move', $signatureMove)->exists()) {
            throw NameTakenException::signatureMove($signatureMove);
        }
    }
}
