<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Managers\ClearInjuryController;
use App\Http\Requests\Managers\ClearInjuryRequest;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class ClearInjuryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_marks_an_injured_manager_as_being_cleared_and_redirects($administrators)
    {
        $manager = Manager::factory()->injured()->create();

        $this->assertNull($manager->injuries->last()->ended_at);
        $this->assertEquals(ManagerStatus::INJURED, $manager->status);

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager))
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->injuries->last()->ended_at);
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ClearInjuryController::class, '__invoke', ClearInjuryRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_mark_an_injured_manager_as_cleared()
    {
        $manager = Manager::factory()->injured()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('managers.clear-from-injury', $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_mark_an_injured_manager_as_cleared()
    {
        $manager = Manager::factory()->injured()->create();

        $this->patch(route('managers.clear-from-injury', $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_an_unemployed_manager($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_available_manager($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->available()->create();

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_future_employed_manager($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_suspended_manager($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_retired_manager($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_released_manager($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager));
    }
}
