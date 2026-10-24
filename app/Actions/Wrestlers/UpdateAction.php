<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Lifecycle\ChangeScheduledEmploymentAction;
use App\Actions\Managers\SynchronizeManagerAssignmentsAction;
use App\Data\Wrestlers\WrestlerData;
use App\Enums\Naming\GuardedName;
use App\Exceptions\Lifecycle\EmploymentDateException;
use App\Exceptions\Roster\Wrestlers\NameTakenException;
use App\Lifecycle\Naming\GuardedNameRequirements;
use App\Lifecycle\Naming\RecordNameLock;
use App\Lifecycle\Roster\EmploymentDateRequirements;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Support\Carbon;
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
 * - Synchronizes the managers when WrestlerData carries them (null leaves them alone); newly added managers must be available
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
        protected ChangeScheduledEmploymentAction $changeScheduledEmployment,
        protected RecordNameLock $nameLock,
        protected SynchronizeManagerAssignmentsAction $synchronizeManagerAssignmentsAction,
        protected EmploymentDateRequirements $employmentDates,
        protected GuardedNameRequirements $nameRequirements,
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
     * @throws EmploymentDateException When the date is another day than the one employment started, or a scheduled hire moves to a day before today
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

            // A wrestler who is employed keeps the date employment started; another one is refused, not dropped.
            if ($wrestlerData->employment_date instanceof Carbon) {
                $this->employmentDates->ensureCurrentStartIsUnchanged($lockedWrestler, $wrestlerData->employment_date);
            }

            $lockedWrestler->update([
                'name' => $name,
                'height' => $wrestlerData->height,
                'weight' => $wrestlerData->weight,
                'hometown' => $wrestlerData->hometown,
                'signature_move' => $wrestlerData->signature_move,
            ]);

            $this->synchronizeManagerAssignmentsAction->handle($lockedWrestler, $wrestlerData->managers, now());

            // A wrestler whose only employment has not started yet has it moved to the form date, or cancelled when the
            // date is cleared.
            if ($this->changeScheduledEmployment->handle($lockedWrestler, $wrestlerData->employment_date)) {
                return $lockedWrestler;
            }

            // Only a wrestler with no employment history is employed from the form date; a released, retired or
            // started wrestler keeps their history (employing again would overlap or be rejected).
            if (! is_null($wrestlerData->employment_date) && ! $lockedWrestler->employments()->exists()) {
                $this->employAction->handle($lockedWrestler, $wrestlerData->employment_date);
            }

            return $lockedWrestler;
        });
    }

    private function ensureNameIsAvailable(Wrestler $wrestler, string $name, ?string $signatureMove): void
    {
        $wrestlers = Wrestler::query()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->withTrashed()
            ->where('promotion_id', $wrestler->promotion_id);

        $this->nameRequirements->ensureAvailable($wrestlers, 'name', $name, fn (): NameTakenException => NameTakenException::name($name), except: $wrestler);

        if ($signatureMove !== null) {
            $this->nameRequirements->ensureAvailable(
                $wrestlers,
                'signature_move',
                $signatureMove,
                fn (): NameTakenException => NameTakenException::signatureMove($signatureMove),
                except: $wrestler,
            );
        }
    }
}
