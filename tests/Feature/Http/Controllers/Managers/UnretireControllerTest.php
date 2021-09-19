<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\UnretireController;
use App\Http\Requests\Managers\UnretireRequest;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_unretires_a_retired_manager_and_redirects()
    {
        $manager = Manager::factory()->retired()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->retirements->last()->ended_at);
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(UnretireController::class, '__invoke', UnretireRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_unretire_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(action([UnretireController::class], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_unretire_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->patch(action([UnretireController::class], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_an_available_manager()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->available()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_a_future_employed_manager()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_an_injured_manager()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->injured()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_a_released_manager()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_a_suspended_manager()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->suspended()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $manager));
    }

    /**
     * @test
     * @dataProvider nonunretirableManagerTypes
     */
    public function invoke_throws_exception_for_unretiring_a_non_unretirable_manager($factoryState)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->{$factoryState}()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $manager));
    }

    public function nonunretirableManagerTypes()
    {
        return [
            'available manager' => ['available'],
            'with future employed manager' => ['withFutureEmployment'],
            'injured manager' => ['injured'],
            'released manager' => ['released'],
            'suspended manager' => ['suspended'],
            'unemployed manager' => ['unemployed'],
        ];
    }
}
