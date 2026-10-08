<?php

declare(strict_types=1);

namespace App\Rules\Stables;

use App\Models\Contracts\CanBeAStableMember;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Suspendable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

class CanJoinStable implements ValidationRule
{
    /**
     * @param  class-string<Model>  $memberClass
     */
    public function __construct(
        private readonly string $memberClass,
        private readonly ?int $stableId = null,
        private readonly ?Carbon $stableStartDate = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail(__('stables.validation.invalid_member'));

            return;
        }

        $member = $this->memberClass::query()->find($value);

        if (! $member instanceof Model) {
            $fail(__('stables.validation.invalid_member'));

            return;
        }

        if (! $member instanceof CanBeAStableMember ||
            ! $member instanceof Employable ||
            ! $member instanceof Suspendable) {
            throw new LogicException("{$this->memberClass} must be an employable, suspendable Stable member.");
        }

        if ($this->stableId !== null && $member->stables()
            ->whereKey($this->stableId)
            ->wherePivotNull('left_at')
            ->exists()) {
            return;
        }

        $currentStable = $member->currentStable()->first();

        if ($currentStable) {
            $fail(__('stables.validation.member_already_in_stable'));

            return;
        }

        if ($member->currentSuspension()->exists()) {
            $fail(__('stables.validation.member_suspended'));

            return;
        }

        if (! $member->currentEmployment()->exists()) {
            $fail(__('stables.validation.member_not_employed'));

            return;
        }

        if ($this->stableStartDate instanceof Carbon && ! $member->currentEmployment()
            ->where('started_at', '<=', $this->stableStartDate)
            ->exists()) {
            $fail(__('stables.validation.employment_after_start'));
        }
    }
}
