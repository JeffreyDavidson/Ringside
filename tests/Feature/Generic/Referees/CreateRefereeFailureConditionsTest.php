<?php

namespace Tests\Feature\Generic\Referees;

use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 */
class CreateRefereeFailureConditionsTest extends TestCase
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
    public function a_referee_first_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('referees.create'))
                        ->post(route('referees.store'), $this->validParams([
                            'first_name' => ''
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('referees.create'));
        $response->assertSessionHasErrors('first_name');
        $this->assertEquals(0, Referee::count());
    }

    /** @test */
    public function a_referee_first_name_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('referees.create'))
                        ->post(route('referees.store'), $this->validParams([
                            'first_name' => ['not-a-string']
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('referees.create'));
        $response->assertSessionHasErrors('first_name');
        $this->assertEquals(0, Referee::count());
    }

    /** @test */
    public function a_referee_last_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('referees.create'))
                        ->post(route('referees.store'), $this->validParams([
                            'last_name' => ''
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('referees.create'));
        $response->assertSessionHasErrors('last_name');
        $this->assertEquals(0, Referee::count());
    }

    /** @test */
    public function a_referee_last_name_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('referees.create'))
                        ->post(route('referees.store'), $this->validParams([
                            'last_name' => ['not-a-string']
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('referees.create'));
        $response->assertSessionHasErrors('last_name');
        $this->assertEquals(0, Referee::count());
    }

    /** @test */
    public function a_referee_started_at_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('referees.create'))
                        ->post(route('referees.store'), $this->validParams([
                            'started_at' => ['not-a-string']
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('referees.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Referee::count());
    }

    /** @test */
    public function a_referee_started_at_must_be_in_datetime_format_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('referees.create'))
                        ->post(route('referees.store'), $this->validParams([
                            'started_at' => now()->toDateString()
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('referees.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Referee::count());
    }
}
