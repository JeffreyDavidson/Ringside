<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Managers\UnretireController;
use App\Http\Requests\Managers\UnretireRequest;
use App\Models\Manager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group srm
 * @group feature-srm
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
    public function invoke_unretires_a_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = Manager::factory()->retired()->create();

        $response = $this->unretireRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
            $this->assertCount(1, $manager->retirements);
            $this->assertEquals($now->toDateTimeString(), $manager->retirements->first()->ended_at->toDateTimeString());
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
    public function a_basic_user_cannot_unretire_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = Manager::factory()->create();

        $this->unretireRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->unretireRequest($manager)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_available_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->available()->create();

        $this->unretireRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_future_employed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->unretireRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_injured_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->injured()->create();

        $this->unretireRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_released_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->released()->create();

        $this->unretireRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_suspended_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->suspended()->create();

        $this->unretireRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_unemployed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->unemployed()->create();

        $this->unretireRequest($manager);
    }
}
