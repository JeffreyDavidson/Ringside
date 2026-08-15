<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Lifecycle\Retirement;
use Illuminate\Database\Eloquent\Builder;

/** @extends Builder<Retirement> */
final class RetirementStateBuilder extends Builder {}
