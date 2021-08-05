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
     * @dataProvider employableReferees
     */
    public function invoke_employs_an_employable_referee_and_redirects($employableReferees)
    {
        dd($employableReferees);
        $this->actAs(Role::ADMINISTRATOR)
            ->patch(route('referees.employ', $employableReferees))
            ->assertRedirect(route('referees.index'));

        tap($employableReferees->fresh(), function ($referee) {
            $this->assertEquals(RefereeStatus::BOOKABLE, $referee->status);
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EmployController::class, '__invoke', EmployRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_employ_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('referees.employ', $referee))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_referee()
    {
        $referee = Referee::factory()->create();

        $this->patch(route('referees.employ', $referee))
            ->assertRedirect(route('login'));
    }

    public function employableReferees()
    {
        return [
            // 'released' => ['testing'],
            'released' => [Referee::factory()->released()->create()],
            // 'has_future_employment' => [Referee::factory()->withFutureEmployment()->create()],
            // 'unemployed' => [Referee::factory()->unemployed()->create()],
        ];
    }
}
