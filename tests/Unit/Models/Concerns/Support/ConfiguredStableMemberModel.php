<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\CanJoinStables;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Stables\StableWrestler;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CanBeAStableMember<StableWrestler, self>
 */
class ConfiguredStableMemberModel extends Model implements CanBeAStableMember
{
    /** @use CanJoinStables<StableWrestler, self> */
    use CanJoinStables;

    protected function stableMembershipTable(): string
    {
        return (new StableWrestler())->getTable();
    }

    protected function stableMembershipForeignKey(): string
    {
        return 'wrestler_id';
    }

    protected function stableMembershipPivotModel(): string
    {
        return StableWrestler::class;
    }
}
