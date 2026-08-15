<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Lifecycle\Suspension;
use Illuminate\Database\Eloquent\Builder;

/** @extends Builder<Suspension> */
final class SuspensionStateBuilder extends Builder {}
