<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Http\Controllers\Stables\DeactivateController;
use App\Http\Requests\Stables\DeactivateRequest;
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
class DeactivateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_deactivates_a_stable($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $stable = StableFactory::new()->activate()->create();

        $response = $this->deactivateRequest($stable);

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::INACTIVE, $stable->status);
            $this->assertCount(1, $stable->activations);
            $this->assertEquals($now->toDateTimeString(), $stable->activations->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_deactivates_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->create();

        $this->deactivateRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_deactivates_a_stable()
    {
        $stable = StableFactory::new()->create();

        $this->deactivateRequest($stable)->assertRedirect(route('login'));
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            DeactivateController::class,
            '__invoke',
            DeactivateRequest::class
        );
    }
}
