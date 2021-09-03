<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReleaseController;
use App\Http\Requests\Managers\ReleaseRequest;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-rosters
 */
class ReleaseControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_releases_an_available_manager_and_redirects()
    {
        $manager = Manager::factory()->available()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->employments->last()->ended_at);
            $this->assertEquals(ManagerStatus::RELEASED, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_an_injured_manager_and_redirects()
    {
        $manager = Manager::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->injuries->last()->ended_at);
            $this->assertNotNull($manager->employments->last()->ended_at);
            $this->assertEquals(ManagerStatus::RELEASED, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_a_suspended_manager_and_redirects()
    {
        $manager = Manager::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->suspensions->last()->ended_at);
            $this->assertNotNull($manager->employments->last()->ended_at);
            $this->assertEquals(ManagerStatus::RELEASED, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_a_manager_leaving_their_current_tag_teams_and_wrestlers_and_redirects()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = Wrestler::factory()->bookable()->create();

        $manager = Manager::factory()
            ->available()
            ->hasAttached($tagTeam, ['hired_at' => now()->toDateTimeString()])
            ->hasAttached($wrestler, ['hired_at' => now()->toDateTimeString()])
            ->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) use ($tagTeam, $wrestler) {
            $this->assertNotNull($manager->tagTeams()->where('manageable_id', $tagTeam->id)->get()->last()->pivot->left_at);
            $this->assertNotNull($manager->wrestlers()->where('manageable_id', $wrestler->id)->get()->last()->pivot->left_at);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ReleaseController::class, '__invoke', ReleaseRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([ReleaseController::class], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_release_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_releasing_an_unemployed_manager()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReleaseController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_releasing_a_future_employed_manager()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReleaseController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_releasing_a_released_manager()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReleaseController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_releasing_a_retired_manager()
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReleaseController::class], $manager));
    }
}
