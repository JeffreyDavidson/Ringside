<?php

namespace Tests\Unit\Models;

use App\Models\SingleRosterMember;
use Tests\TestCase;

/**
 * @group roster
 * @group models
 */
class SingleRosterMemberTest extends TestCase
{
    /**
     * @test
     */
    public function a_single_roster_member_uses_suspendable_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\Suspendable', SingleRosterMember::class);
    }

    /**
     * @test
     */
    public function a_single_roster_member_uses_injurable_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\Injurable', SingleRosterMember::class);
    }

    /**
     * @test
     */
    public function a_single_roster_member_uses_retirable_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\Retirable', SingleRosterMember::class);
    }

    /**
     * @test
     */
    public function a_single_roster_member_uses_employable_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\Employable', SingleRosterMember::class);
    }

    /**
     * @test
     */
    public function a_single_roster_member_uses_releasable_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\Releasable', SingleRosterMember::class);
    }
}
