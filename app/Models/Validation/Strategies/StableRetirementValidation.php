<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Models\Contracts\RetirementValidationStrategy;
use Illuminate\Database\Eloquent\Model;

class StableRetirementValidation implements RetirementValidationStrategy
{
    public function validate(Model $stable): void
    {
        // Allow all for now (stub)
    }
}
