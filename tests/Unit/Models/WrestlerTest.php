<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\WrestlerStatus;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\SingleRosterMember;
use App\Models\Wrestler;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group roster
 * @group models
 */
class WrestlerTest extends TestCase
{
    /**
     * @test
     */
    public function a_wrestler_is_a_single_roster_member()
    {
        $this->assertEquals(SingleRosterMember::class, get_parent_class(Wrestler::class));
    }

    /**
     * @test
     */
    public function a_wrestler_status_gets_cast_as_a_wrestler_status_enum()
    {
        $wrestler = Wrestler::factory()->make();

        $this->assertInstanceOf(WrestlerStatus::class, $wrestler->status);
    }

    /**
     * @test
     */
    public function a_wrestler_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Wrestler::class);
    }

    /**
     * @test
     */
    public function a_wrestler_uses_can_join_stables_trait()
    {
        $this->assertUsesTrait(\App\Models\Concerns\CanJoinStables::class, Wrestler::class);
    }

    /**
     * @test
     */
    public function a_wrestler_implements_bookable_interface()
    {
        $this->assertContains(Bookable::class, class_implements(Wrestler::class));
    }

    /**
     * @test
     */
    public function a_wrestler_implements_can_be_stable_member_interface()
    {
        $this->assertContains(CanBeAStableMember::class, class_implements(Wrestler::class));
    }
}
