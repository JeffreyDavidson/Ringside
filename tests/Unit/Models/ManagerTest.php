<?php

namespace Tests\Unit\Models;

use App\Enums\ManagerStatus;
use App\Models\Manager;
use App\Models\SingleRosterMember;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class ManagerTest extends TestCase
{
    /** @test */
    public function a_manager_has_a_first_name()
    {
        $manager = new Manager(['first_name' => 'John']);

        $this->assertEquals('John', $manager->first_name);
    }

    /** @test */
    public function a_manager_has_a_last_name()
    {
        $manager = new Manager(['last_name' => 'Smith']);

        $this->assertEquals('Smith', $manager->last_name);
    }

    /** @test */
    public function a_manager_has_a_status()
    {
        $manager = new Manager();
        $manager->setRawAttributes(['status' => 'example'], true);

        $this->assertEquals('example', $manager->getRawOriginal('status'));
    }

    /** @test */
    public function a_manager_status_is_a_enum()
    {
        $manager = new Manager();

        $this->assertInstanceOf(ManagerStatus::class, $manager->status);
    }

    /** @test */
    public function a_manager_uses_has_a_full_name_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\HasFullName', Manager::class);
    }

    /** @test */
    public function a_manager_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Manager::class);
    }

    /** @test */
    public function a_manager_is_a_single_roster_member()
    {
        $this->assertEquals(SingleRosterMember::class, get_parent_class(Manager::class));
    }
}
