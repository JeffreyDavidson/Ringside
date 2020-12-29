<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Wrestlers\ClearInjuryController;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
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
class ClearInjuryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_marks_an_injured_wrestler_as_being_recovered_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $wrestler = Wrestler::factory()->injured()->create();

        $this->assertCount(1, $wrestler->injuries);
        $this->assertEquals(WrestlerStatus::INJURED, $wrestler->fresh()->status);

        $this->actAs($administrators)
            ->patch(route('wrestlers.clear-from-injury', $wrestler))
            ->assertRedirect(route('wrestlers.index'));

        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
            $this->assertEquals($now->toDateTimeString('minute'), $wrestler->injuries->first()->ended_at->toDateTimeString('minute'));
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injured_wrestler_on_an_unbookable_tag_team_makes_tag_team_bookable($administrators)
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();
        $wrestler->injure();
        $wrestler->currentTagTeam->updateStatusAndSave();

        $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->fresh()->status);

        $this->actAs($administrators)
            ->patch(route('wrestlers.clear-from-injury', $wrestler));

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->fresh()->status);
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ClearInjuryController::class, '__invoke', ClearInjuryRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_mark_an_injured_wrestler_as_recovered()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('wrestlers.clear-from-injury', $wrestler))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_mark_an_injured_wrestler_as_recovered()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this->patch(route('wrestlers.clear-from-injury', $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.clear-from-injury', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_bookable_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.clear-from-injury', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_future_employed_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.clear-from-injury', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_suspended_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.clear-from-injury', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_retired_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.clear-from-injury', $wrestler));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_a_released_wrestler_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('wrestlers.clear-from-injury', $wrestler));
    }
}
