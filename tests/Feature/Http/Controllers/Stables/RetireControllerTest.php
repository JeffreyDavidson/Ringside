<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Stables\RetireController;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Requests\Stables\RetireRequest;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_retires_an_active_stable_and_its_members_and_redirects()
    {
        $stable = Stable::factory()->active()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) {
            $this->assertCount(1, $stable->retirements);
            $this->assertEquals(StableStatus::RETIRED, $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->retirements);
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertCount(1, $tagTeam->retirements);
                $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_retires_an_inactive_stable_and_its_members_and_redirects()
    {
        $stable = Stable::factory()->inactive()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) {
            $this->assertCount(1, $stable->retirements);
            $this->assertEquals(StableStatus::RETIRED, $stable->status);

            foreach ($stable->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->retirements);
                $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
            }

            foreach ($stable->currentTagTeams as $tagTeam) {
                $this->assertCount(1, $tagTeam->retirements);
                $this->assertEquals(TagTeamStatus::RETIRED, $tagTeam->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(RetireController::class, '__invoke', RetireRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_retire_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([RetireController::class], $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_retire_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->patch(action([RetireController::class], $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonretirableStableTypes
     */
    public function invoke_throws_exception_for_retiring_a_non_retirable_stable($factoryState)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([RetireController::class], $stable));
    }

    public function nonretirableStableTypes()
    {
        return [
            'retired stable' => ['retired'],
            'with future activated stable' => ['withFutureActivation'],
            'unactivated stable' => ['unactivated'],
        ];
    }
}
