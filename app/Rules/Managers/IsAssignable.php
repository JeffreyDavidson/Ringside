<?php

declare(strict_types=1);

namespace App\Rules\Managers;

use App\Exceptions\Roster\Managers\CannotBeAssignedException;
use App\Lifecycle\Roster\Managers\ManagerAssignmentEligibility;
use App\Models\Roster\Managers\Manager;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsAssignable implements ValidationRule
{
    /**
     * @param  array<int, int>  $alreadyAssignedIds  Managers already on the record: they stay even if no longer eligible
     */
    public function __construct(private readonly array $alreadyAssignedIds = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! is_string($value)) {
            $fail(__('managers.validation.invalid'));

            return;
        }

        $manager = Manager::query()->whereKey($value)->first();

        if (! $manager instanceof Manager) {
            $fail(__('managers.validation.invalid'));

            return;
        }

        if (in_array($manager->id, $this->alreadyAssignedIds, true)) {
            return;
        }

        try {
            resolve(ManagerAssignmentEligibility::class)->ensureCanBeAssigned($manager);
        } catch (CannotBeAssignedException $cannotBeAssignedException) {
            $fail($cannotBeAssignedException->getMessage());
        }
    }
}
