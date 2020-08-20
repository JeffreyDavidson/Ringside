<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use App\Enums\RefereeStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_referee_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $referee = RefereeFactory::new()->withFutureEmployment()->create();

        $response = $this->employRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($now->toDateTimeString(), $referee->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_employ_a_referee()
    {
        $this->actAs(Role::BASIC);
        $referee = RefereeFactory::new()->withFutureEmployment()->create();

        $this->employRequest($referee)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_referee()
    {
        $referee = RefereeFactory::new()->withFutureEmployment()->create();

        $this->employRequest($referee)->assertRedirect(route('login'));
    }
}
