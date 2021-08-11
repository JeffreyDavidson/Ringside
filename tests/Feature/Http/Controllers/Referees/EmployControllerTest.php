<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Http\Controllers\Referees\EmployController;
use App\Http\Requests\Referees\EmployRequest;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group srm
 * @group feature-srm
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_an_employable_referee_and_redirects($administrators)
    {
        $referee = Referee::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('referees.employ', $referee))
            ->assertRedirect(route('referees.index'));

        tap($referee->fresh(), function ($referee) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_bookable_referee_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $referee = Referee::factory()->bookable()->create();

        $this->actAs($administrators)
            ->patch(route('referees.employ', $referee));
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EmployController::class, '__invoke', EmployRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_employ_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('referees.employ', $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_employ_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->patch(route('referees.employ', $referee))
            ->assertRedirect(route('login'));
    }
}
