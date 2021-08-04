<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Services\WrestlerService;
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
        $wrestler = Wrestler::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler))
            ->assertRedirect(route('wrestlers.index'));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertTrue($wrestler->isBookable());
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
        app(WrestlerService::class)->suspend($wrestler);
        $wrestler->currentTagTeam->updateStatusAndSave();

        $this->actAs($administrators)
            ->patch(route('wrestlers.reinstate', $wrestler));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertTrue($tagTeam->isBookable());
        });
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
