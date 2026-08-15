<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Lifecycle\Employment;
use Illuminate\Database\Eloquent\Builder;

/** @extends Builder<Employment> */
final class EmploymentStateBuilder extends Builder {}
