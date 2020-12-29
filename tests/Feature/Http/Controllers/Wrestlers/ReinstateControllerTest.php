<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
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
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_reinstates_a_suspended_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $wrestler = Wrestler::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler))
            ->assertRedirect(route('wrestlers.index'));

        $this->assertEquals($now->toDateTimeString('minute'), $wrestler->fresh()->suspensions()->latest()->first()->ended_at->toDateTimeString('minute'));

        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertCount(1, $wrestler->suspensions);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->suspensions->first()->ended_at->toDateTimeString('minute'));
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_suspended_wrestler_on_an_unbookable_tag_team_makes_tag_team_bookable($administrators)
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();
        $wrestler->suspend();
        $wrestler->currentTagTeam->updateStatusAndSave();

        $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->fresh()->status);

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler));

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->fresh()->status);
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ReinstateController::class, '__invoke', ReinstateRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('wrestlers.reinstate', $wrestler))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->patch(route('wrestlers.reinstate', $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_bookable_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_injured_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->injured()->create();

        $this->actAs($administrators)->patch(route('wrestlers.reinstate', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler));
    }
}
