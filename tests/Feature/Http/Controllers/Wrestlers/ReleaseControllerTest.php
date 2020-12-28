<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Wrestlers\ReleaseController;
use App\Http\Requests\Wrestlers\ReleaseRequest;
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
class ReleaseControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_a_bookable_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->bookable()->create();

        $response = $this->patch(route('wrestlers.release', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->employments->first()->ended_at->toDateTimeString('minute'));
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_an_injured_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->injured()->create();

        $response = $this->patch(route('wrestlers.release', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->employments->first()->ended_at->toDateTimeString('minute'));
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->injuries->first()->ended_at->toDateTimeString('minute'));
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_a_suspended_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->suspended()->create();

        $response = $this->patch(route('wrestlers.release', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->employments->first()->ended_at->toDateTimeString('minute'));
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->suspensions->first()->ended_at->toDateTimeString('minute'));
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable($administrators)
    {
        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

        $response = $this->patch(route('wrestlers.release', $wrestler));

        $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->fresh()->status);
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ReleaseController::class, '__invoke', ReleaseRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = Wrestler::factory()->create();

        $this->patch(route('wrestlers.release', $wrestler))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_release_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->patch(route('wrestlers.release', $wrestler))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->patch(route('wrestlers.release', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->patch(route('wrestlers.release', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->released()->create();

        $this->patch(route('wrestlers.release', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->retired()->create();

        $this->patch(route('wrestlers.release', $wrestler));
    }
}
