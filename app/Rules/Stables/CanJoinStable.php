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

/**
 * @template TMember of Model&CanBeAStableMember&Employable&Suspendable
 */
class CanJoinStable implements ValidationRule
{
    /**
     * @param  class-string<TMember>  $memberClass
     */
    public function __construct(
        private string $memberClass,
        private ?int $stableId = null,
        private ?Carbon $stableStartDate = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail('The selected stable member is invalid.');

            return;
        }

        $member = $this->memberClass::query()->find($value);

        if (! $member instanceof Model) {
            $fail('The selected stable member is invalid.');

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
            $fail('This member already belongs to another stable.');

            return;
        }

        if ($member->isSuspended()) {
            $fail('This member is suspended and cannot join the stable.');

            return;
        }

        if (! $member->isEmployed()) {
            $fail('This member is not employed and cannot join the stable.');

            return;
        }

        if ($this->stableStartDate && ! $member->currentEmployment()
            ->where('started_at', '<=', $this->stableStartDate)
            ->exists()) {
            $fail("This member's employment must begin on or before the stable's start date.");
        }
    }
}
