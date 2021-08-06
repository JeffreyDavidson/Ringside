<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Wrestlers\RetireController;
use App\Http\Requests\Wrestlers\RetireRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group srm
 * @group feature-srm
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
    public function invoke_retires_a_bookable_wrestler_and_redirects($administrators)
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.retire', $wrestler))
            ->assertRedirect(route('wrestlers.index'));

        $wrestler->refresh();

        $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
        $this->assertCount(1, $wrestler->retirements);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_an_injured_wrestler_and_redirects($administrators)
    {
        $this->withoutExceptionHandling();
        $wrestler = Wrestler::factory()->injured()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.retire', $wrestler))
            ->assertRedirect(route('wrestlers.index'));

        $wrestler->refresh();

        $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
        $this->assertCount(1, $wrestler->retirements);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_suspended_wrestler_and_redirects($administrators)
    {
        $wrestler = Wrestler::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.retire', $wrestler))
            ->assertRedirect(route('wrestlers.index'));

        $wrestler->refresh();

        $this->assertEquals(WrestlerStatus::RETIRED, $wrestler->status);
        $this->assertCount(1, $wrestler->retirements);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable($administrators)
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

        $this->actAs($administrators)
            ->patch(route('wrestlers.retire', $wrestler));

        $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->refresh()->status);
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
    public function a_basic_user_cannot_retire_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('wrestlers.retire', $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_retire_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->patch(route('wrestlers.retire', $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.retire', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.retire', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.retire', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.retire', $wrestler));
    }
}
