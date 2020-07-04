<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class EmployRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_employ_a_pending_employment_referee($adminRoles)
    {
        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function (Referee $referee) {
            $this->assertTrue($referee->isCurrentlyEmployed);
        });
    }

    /** @test */
    public function a_referee_without_a_current_employment_can_be_employed()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $referee = RefereeFactory::new()->create();

        $response = $this->employRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) {
            $this->assertTrue($referee->currentEmployment()->exists());
        });
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
