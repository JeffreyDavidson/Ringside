<?php

namespace Tests\Unit\Models;

use App\Enums\ManagerStatus;
use App\Models\Manager;
use App\Models\SingleRosterMember;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 * @group models
 */
class ManagerTest extends TestCase
{
    /**
     * @test
     */
    public function a_manager_status_gets_cast_as_a_manager_status_enum()
    {
        $manager = Manager::factory()->make();

        $this->assertInstanceOf(ManagerStatus::class, $manager->status);
    }

    /**
     * @test
     */
    public function a_manager_is_a_single_roster_member()
    {
        $this->assertEquals(SingleRosterMember::class, get_parent_class(Manager::class));
    }

    /**
     * @test
     */
    public function a_manager_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Manager::class);
    }

    /**
     * @test
     */
    public function a_manager_uses_has_a_full_name_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\HasFullName', Manager::class);
    }
}
