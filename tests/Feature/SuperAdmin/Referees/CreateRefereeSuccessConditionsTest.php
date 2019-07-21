<?php

namespace Tests\Feature\SuperAdmin\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group superadmins
 */
class CreateRefereeSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'started_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_super_administrator_can_view_the_form_for_creating_a_referee()
    {
        $this->actAs('super-administrator');

        $response = $this->get(route('referees.create'));

        $response->assertViewIs('referees.create');
        $response->assertViewHas('referee', new Referee);
    }

    /** @test */
    public function a_super_administrator_can_create_a_referee()
    {
        $this->actAs('super-administrator');

        $response = $this->from(route('referees.create'))
                        ->post(route('referees.store'), $this->validParams());
                        
        $response->assertRedirect(route('referees.index'));
        tap(Referee::first(), function ($referee) {
            $this->assertEquals('John', $referee->first_name);
            $this->assertEquals('Smith', $referee->last_name);
            $this->assertEquals(now()->toDateTimeString(), $referee->employment->started_at->toDateTimeString());
        });
    }
}
