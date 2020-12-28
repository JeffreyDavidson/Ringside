<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Stables\ActivateController;
use App\Http\Requests\Stables\ActivateRequest;
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
class ActivateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_an_unactivated_stable_with_members_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $stable = Stable::factory()->unactivated()->create();

        $response = $this->patch(route('stables.activate', $stable));

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);
            $this->assertCount(1, $stable->activations);
            $this->assertEquals($now->toDateTimeString(), $stable->activations->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_a_future_activated_stable_with_members_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $stable = Stable::factory()->withFutureActivation()->create();

        $response = $this->patch(route('stables.activate', $stable));

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);
            $this->assertCount(1, $stable->activations);
            $this->assertEquals($now->toDateTimeString(), $stable->activations->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_an_inactive_stable_with_members_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $stable = Stable::factory()->inactive()->create();

        $response = $this->patch(route('stables.activate', $stable));

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);
            $this->assertCount(2, $stable->activations);
            $this->assertEquals($now->toDateTimeString(), $stable->activations->last()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ActivateController::class, '__invoke', ActivateRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_activate_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create();

        $this->patch(route('stables.activate', $stable))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_activate_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->patch(route('stables.activate', $stable))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function activating_an_retired_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $stable = Stable::factory()->retired()->create();

        $this->patch(route('stables.activate', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function activating_an_active_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $stable = Stable::factory()->active()->create();

        $this->patch(route('stables.activate', $stable));
    }
}
