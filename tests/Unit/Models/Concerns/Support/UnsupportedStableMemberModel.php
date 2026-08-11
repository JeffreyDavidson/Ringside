<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\CanJoinStables;
use App\Models\Contracts\CanBeAStableMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @implements CanBeAStableMember<Pivot, self>
 */
class UnsupportedStableMemberModel extends Model implements CanBeAStableMember
{
    /** @use CanJoinStables<Pivot, self> */
    use CanJoinStables;
}
