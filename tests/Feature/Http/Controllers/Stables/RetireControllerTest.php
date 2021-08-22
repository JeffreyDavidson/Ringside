<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Stables\RetireController;
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
     * @dataProvider administrators
     */
    public function invoke_retires_an_active_stable_and_its_members_and_redirects($administrators)
    {
        $stable = Stable::factory()->active()->create();

        $this->actAs($administrators)
            ->patch(route('stables.retire', $stable))
            ->assertRedirect(route('stables.index'));

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

        $this->actAs(Role::BASIC)
            ->patch(route('stables.retire', $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_retire_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->patch(route('stables.retire', $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_retiring_a_retired_stable($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('stables.retire', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_retiring_a_future_activated_stable($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->withFutureActivation()->create();

        $this->actAs($administrators)
            ->patch(route('stables.retire', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_retiring_an_unactivated_stable($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->unactivated()->create();

        $this->actAs($administrators)
            ->patch(route('stables.retire', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_retiring_an_inactive_stable($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->inactive()->create();

        $this->actAs($administrators)
            ->patch(route('stables.retire', $stable));
    }
}
