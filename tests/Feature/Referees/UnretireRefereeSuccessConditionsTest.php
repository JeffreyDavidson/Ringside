<?php

namespace Tests\Feature\Referees;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 */
class UnretireRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_unretire_a_retired_referee($adminRoles)
    {
        $this->actAs($adminRoles);
        $referee = RefereeFactory::new()->retired()->create();

        $response = $this->unretireRequest($referee);

        $response->assertRedirect(route('referees.index'));
        $this->assertEquals(now()->toDateTimeString(), $referee->fresh()->retirements()->latest()->first()->ended_at);
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
