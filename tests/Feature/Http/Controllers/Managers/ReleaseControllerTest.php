<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReleaseController;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-rosters
 */
class ReleaseControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_releases_an_available_manager_and_redirects()
    {
        $manager = Manager::factory()->available()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->employments->last()->ended_at);
            $this->assertEquals(ManagerStatus::released(), $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_an_injured_manager_and_redirects()
    {
        $manager = Manager::factory()->injured()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->injuries->last()->ended_at);
            $this->assertNotNull($manager->employments->last()->ended_at);
            $this->assertEquals(ManagerStatus::released(), $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_releases_a_suspended_manager_and_redirects()
    {
        $manager = Manager::factory()->suspended()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->suspensions->last()->ended_at);
            $this->assertNotNull($manager->employments->last()->ended_at);
            $this->assertEquals(ManagerStatus::released(), $manager->status);
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
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) use ($tagTeam, $wrestler) {
            $this->assertNotNull(
                $manager->tagTeams()->where('manageable_id', $tagTeam->id)->get()->last()->pivot->left_at
            );
            $this->assertNotNull(
                $manager->wrestlers()->where('manageable_id', $wrestler->id)->get()->last()->pivot->left_at
            );
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::basic())
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
     *
     * @dataProvider nonreleasableManagerTypes
     */
    public function invoke_throws_exception_for_releasing_a_non_releasable_manager($factoryState)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReleaseController::class], $manager));
    }

    public function nonreleasableManagerTypes()
    {
        return [
            'unemployed manager' => ['unemployed'],
            'with future employed manager' => ['withFutureEmployment'],
            'released manager' => ['released'],
            'retired manager' => ['retired'],
        ];
    }
}
