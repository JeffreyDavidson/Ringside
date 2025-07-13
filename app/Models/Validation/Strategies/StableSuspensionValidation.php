<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Models\Contracts\SuspensionValidationStrategy;
use Illuminate\Database\Eloquent\Model;

class StableSuspensionValidation implements SuspensionValidationStrategy
{
    public function validate(Model $stable): void
    {
        // Allow all for now (stub)
    }
}
