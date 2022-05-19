<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\RefereeStatus;
use App\Models\Referee;
use App\Models\SingleRosterMember;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 * @group models
 */
class RefereeTest extends TestCase
{
    /**
     * @test
     */
    public function a_referee_status_gets_cast_as_a_referee_status_enum()
    {
        $referee = Referee::factory()->make();

        $this->assertInstanceOf(RefereeStatus::class, $referee->status);
    }

    /**
     * @test
     */
    public function a_referee_is_a_single_roster_member()
    {
        $this->assertEquals(SingleRosterMember::class, get_parent_class(Referee::class));
    }

    /**
     * @test
     */
    public function a_referee_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Referee::class);
    }

    /**
     * @test
     */
    public function a_referee_uses_has_a_full_name_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\HasFullName', Referee::class);
    }
}
