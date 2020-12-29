<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Requests\Wrestlers\SuspendRequest;
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
 * @group feature-rosters
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_suspends_a_bookable_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $wrestler = Wrestler::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.suspend', $wrestler))
            ->assertRedirect(route('wrestlers.index'));

        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::SUSPENDED, $wrestler->status);
            $this->assertCount(1, $wrestler->suspensions);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->suspensions->first()->started_at->toDateTimeString('minute'));
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable($administrators)
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

        $this->actAs($administrators)
            ->patch(route('wrestlers.suspend', $wrestler));

        $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->fresh()->status);
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(SuspendController::class, '__invoke', SuspendRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('wrestlers.suspend', $wrestler))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_suspend_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->patch(route('wrestlers.suspend', $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.suspend', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.suspend', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_injured_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->injured()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.suspend', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.suspend', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.suspend', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.suspend', $wrestler));
    }
}
