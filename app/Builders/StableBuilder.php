<?php

declare(strict_types=1);

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class StableBuilder extends Builder
{
    use Concerns\HasActivations;
    use Concerns\HasRetirements;
}
