<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Exceptions\CannotBeDisbandedException;
use App\Http\Controllers\Stables\DisbandController;
use App\Http\Requests\Stables\DisbandRequest;
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
class DisbandControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_disbands_an_active_stable_and_its_members_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $stable = Stable::factory()->active()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disband', $stable))
            ->assertRedirect(route('stables.index'));

        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::INACTIVE, $stable->status);
            $this->assertEquals($now->toDateTimeString(), $stable->activations->last()->ended_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(DisbandController::class, '__invoke', DisbandRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_disband_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('stables.disband', $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_disband_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->patch(route('stables.disband', $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function disbanding_an_inactive_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDisbandedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->inactive()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disband', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function disbanding_a_retired_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDisbandedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disband', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function disbanding_an_unactivated_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDisbandedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->unactivated()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disband', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function disbanding_a_future_activated_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDisbandedException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->withFutureActivation()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disband', $stable));
    }
}
