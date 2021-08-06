<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Managers\ClearInjuryController;
use App\Http\Requests\Managers\ClearInjuryRequest;
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
class ClearFromInjuryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_marks_an_injured_manager_as_being_recovered_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->injured()->create();

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager))
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
            $this->assertCount(1, $manager->injuries);
            $this->assertEquals($now->toDateTimeString(), $manager->injuries->first()->ended_at->toDateTimeString());
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
    public function a_basic_user_cannot_mark_an_injured_manager_as_recovered()
    {
        $manager = Manager::factory()->injured()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('managers.clear-from-injury', $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_mark_an_injured_manager_as_recovered()
    {
        $manager = Manager::factory()->injured()->create();

        $this->patch(route('managers.clear-from-injury', $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_an_unemployed_manager_throws_an_exception($administrators)
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
    public function clearing_an_injury_from_a_available_manager_throws_an_exception($administrators)
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
    public function clearing_an_injury_from_a_future_employed_manager_throws_an_exception($administrators)
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
    public function clearing_an_injury_from_a_suspended_manager_throws_an_exception($administrators)
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
    public function clearing_an_injury_from_a_retired_manager_throws_an_exception($administrators)
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
    public function clearing_an_injury_from_a_released_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('managers.clear-from-injury', $manager));
    }
}
