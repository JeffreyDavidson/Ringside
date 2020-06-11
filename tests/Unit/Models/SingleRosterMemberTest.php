<?php

namespace Tests\Unit\Models;

use App\Models\SingleRosterMember;
use Tests\TestCase;

/**
 * @group roster
 */
class SingleRosterMemberTest extends TestCase
{
    /** @test */
    public function a_single_roster_member_uses_can_be_suspended_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeSuspended', SingleRosterMember::class);
    }

    /** @test */
    public function a_single_roster_member_uses_can_be_injured_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeInjured', SingleRosterMember::class);
    }

    /** @test */
    public function a_single_roster_member_uses_can_be_retired_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeRetired', SingleRosterMember::class);
    }

    /** @test */
    public function a_single_roster_member_uses_can_be_employed_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeEmployed', SingleRosterMember::class);
    }

    /** @test */
    public function a_single_roster_member_uses_can_be_booked_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeBooked', SingleRosterMember::class);
    }
}
