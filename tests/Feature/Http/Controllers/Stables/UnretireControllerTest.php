<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Stables\UnretireController;
use App\Http\Requests\Stables\UnretireRequest;
use App\Models\Stable;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_unretires_a_retired_stable_and_its_members_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $stable = Stable::factory()->retired()->create();

        $response = $this->unretireRequest($stable);

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);
            $this->assertCount(1, $stable->retirements);
            $this->assertEquals($now->toDateTimeString(), $stable->fresh()->retirements()->latest()->first()->ended_at);
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            UnretireController::class,
            '__invoke',
            UnretireRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create();

        $this->retireRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->retireRequest($stable)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_active_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $stable = Stable::factory()->active()->create();

        $this->unretireRequest($stable);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_future_activated_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $stable = Stable::factory()->withFutureActivation()->create();

        $this->unretireRequest($stable);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_inactive_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $stable = Stable::factory()->inactive()->create();

        $this->unretireRequest($stable);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_unactivated_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $stable = Stable::factory()->unactivated()->create();

        $this->unretireRequest($stable);
    }
}
