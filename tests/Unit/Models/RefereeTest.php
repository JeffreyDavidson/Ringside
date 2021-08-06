<?php

namespace Tests\Unit\Models;

use App\Enums\RefereeStatus;
use App\Models\Referee;
use App\Models\SingleRosterMember;
use Carbon\Carbon;
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
    public function a_referee_has_a_first_name()
    {
        $referee = new Referee(['first_name' => 'John']);

        $this->assertEquals('John', $referee->first_name);
    }

    /**
     * @test
     */
    public function a_referee_has_a_last_name()
    {
        $referee = new Referee(['last_name' => 'Smith']);

        $this->assertEquals('Smith', $referee->last_name);
    }

    /**
     * @test
     */
    public function a_referee_has_a_status()
    {
        $referee = new Referee();
        $referee->setRawAttributes(['status' => 'example'], true);

        $this->assertEquals('example', $referee->getRawOriginal('status'));
    }

    /**
     * @test
     */
    public function a_referee_status_gets_cast_as_a_referee_status_enum()
    {
        $referee = new Referee();

        $this->assertInstanceOf(RefereeStatus::class, $referee->status);
    }

    /**
     * @test
     */
    public function a_referee_uses_has_a_full_name_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\HasFullName', Referee::class);
    }

    /**
     * @test
     */
    public function a_referee_uses_can_be_booked_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeBooked', Referee::class);
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
    public function a_referee_is_a_single_roster_member()
    {
        $this->assertEquals(SingleRosterMember::class, get_parent_class(Referee::class));
    }

    /**
     * @test
     */
    public function employing_a_released_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $referee = Referee::factory()->released()->create();

        $referee->employ();

        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
            $this->assertCount(2, $referee->employments);
            $this->assertEquals($now->toDateTimeString(), $referee->employments->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_bookable_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('referees.employ', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_retired_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('referees.employ', $referee));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_suspended_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('referees.employ', $referee));
    }

    /**
     * @test
     */
    public function employing_an_injured_referee_throws_an_exception()
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->injured()->create();

        $referee->employ();
    }
}
