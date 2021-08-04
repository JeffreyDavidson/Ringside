<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use Tests\TestCase;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Services\WrestlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
use App\Http\Controllers\Wrestlers\ClearInjuryController;

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
        $wrestler = Wrestler::factory()->injured()->create();
        $response = $this->actAs($administrators)
            ->put(route('wrestlers.clear-from-injury', $wrestler))
            ->assertRedirect(route('wrestlers.index'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injured_wrestler_on_an_unbookable_tag_team_makes_tag_team_bookable($administrators)
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();
        app(WrestlerService::class)->injure($wrestler);
        $wrestler->currentTagTeam->updateStatusAndSave();

        $this->actAs($administrators)
            ->put(route('wrestlers.clear-from-injury', $wrestler));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertTrue($wrestler->isBookable());
        });
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
            ->put(route('wrestlers.clear-from-injury', $wrestler))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_mark_an_injured_wrestler_as_recovered()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this->put(route('wrestlers.clear-from-injury', $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function clearing_an_injury_from_an_unemployed_wrestler_throws_an_exception($administrators)
    {
        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->put(route('wrestlers.clear-from-injury', $wrestler))
            ->assertRedirect()
            ->assertSessionHasErrors('message');
    }
}
