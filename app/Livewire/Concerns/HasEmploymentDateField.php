<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Base\BaseForm;
use App\Models\Lifecycle\Employment;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Shared\CanChangeEmploymentDate;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;

/**
 * The employment-date field of a roster member's form: which state the date is offered in, how a stored start
 * is shown back, the validation rule and how the typed day becomes the instant to store.
 *
 * @phpstan-require-extends BaseForm
 */
trait HasEmploymentDateField
{
    /**
     * Set when editing someone whose employment history is kept, so the date field is not offered. The one
     * exception is an only employment that has not started yet: its date is offered and can be moved or cleared.
     */
    #[Locked]
    public bool $hasEmploymentHistory = false;

    /** Sets the history flag and returns the date to show, or null when no employment is scheduled. */
    protected function loadEmploymentDate(Manager|Referee|Wrestler $model): ?string
    {
        $scheduledEmployment = $model->soleScheduledEmployment();

        $this->hasEmploymentHistory = ! $scheduledEmployment instanceof Employment && $model->employments()->exists();

        return $this->localDateString($scheduledEmployment?->started_at);
    }

    /** @return array<int, mixed> */
    protected function employmentDateRules(Manager|Referee|Wrestler|null $model): array
    {
        return ['nullable', 'date', new CanChangeEmploymentDate($model)];
    }

    protected function employmentDateInstant(?string $value): ?Carbon
    {
        return $value ? $this->parseLocalDate($value) : null;
    }
}
