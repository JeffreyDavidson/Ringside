<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\RetireController;
use App\Models\Manager;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_retires_an_available_manager_and_redirects()
    {
        $manager = Manager::factory()->available()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(1, $manager->retirements);
            $this->assertEquals(ManagerStatus::RETIRED, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_an_injured_manager_and_redirects()
    {
        $manager = Manager::factory()->injured()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(1, $manager->retirements);
            $this->assertEquals(ManagerStatus::RETIRED, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_a_suspended_manager_and_redirects()
    {
        $manager = Manager::factory()->suspended()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(1, $manager->retirements);
            $this->assertEquals(ManagerStatus::RETIRED, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_a_manager_leaving_their_current_tag_teams_and_wrestlers_and_redirects()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = Wrestler::factory()->bookable()->create();

        $manager = Manager::factory()
            ->available()
            ->hasAttached($tagTeam, ['hired_at' => now()->toDateTimeString()])
            ->hasAttached($wrestler, ['hired_at' => now()->toDateTimeString()])
            ->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $manager))
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
    public function a_basic_user_cannot_retire_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([RetireController::class], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_retire_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->patch(action([RetireController::class], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonretirableManagerTypes
     */
    public function invoke_throws_exception_for_retiring_a_non_retirable_manager($factoryState)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $manager));
    }

    public function nonretirableManagerTypes()
    {
        return [
            'retired manager' => ['retired'],
            'with future employed manager' => ['withFutureEmployment'],
            'released manager' => ['released'],
            'unemployed manager' => ['unemployed'],
        ];
    }
}
