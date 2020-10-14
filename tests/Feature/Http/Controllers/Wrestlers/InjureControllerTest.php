<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Requests\Wrestlers\InjureRequest;
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
class InjureControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_injures_a_bookable_wrestler_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $wrestler = Wrestler::factory()->bookable()->create();

        $response = $this->injureRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::INJURED, $wrestler->status);
            $this->assertCount(1, $wrestler->injuries);
            $this->assertEquals($now->toDateTimeString(), $wrestler->injuries->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable($administrators)
    {
        $this->actAs($administrators);

        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

        $response = $this->injureRequest($wrestler);

        $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->fresh()->status);
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            InjureController::class,
            '__invoke',
            InjureRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->employRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->injureRequest($wrestler)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->suspended()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->released()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->retired()->create();

        $this->injureRequest($wrestler);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_an_injured_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $wrestler = Wrestler::factory()->injured()->create();

        $this->injureRequest($wrestler);
    }
}
