<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Http\Controllers\Stables\ActivateController;
use App\Http\Requests\Stables\ActivateRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\StableFactory;
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
    public function invoke_activates_a_stable($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $stable = StableFactory::new()->unactivated()->create();

        $response = $this->activateRequest($stable);

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);
            $this->assertCount(1, $stable->activations);
            $this->assertEquals($now->toDateTimeString(), $stable->activations->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_activate_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->create();

        $this->activateRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_activate_a_stable()
    {
        $stable = StableFactory::new()->create();

        $this->activateRequest($stable)->assertRedirect(route('login'));
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            ActivateController::class,
            '__invoke',
            ActivateRequest::class
        );
    }
}
