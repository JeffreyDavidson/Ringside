<?php

namespace Tests\Feature\Generic\Referee;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 * @group roster
 */
class EmployRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_referee_without_a_current_employment_can_be_employed()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->employRequest($referee);

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) {
            $this->assertTrue($referee->currentEmployment()->exists());
        });
    }
}