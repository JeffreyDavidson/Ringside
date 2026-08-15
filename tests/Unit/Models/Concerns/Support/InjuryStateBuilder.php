<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Lifecycle\Injury;
use Illuminate\Database\Eloquent\Builder;

/** @extends Builder<Injury> */
final class InjuryStateBuilder extends Builder {}
