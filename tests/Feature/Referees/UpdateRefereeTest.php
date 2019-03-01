<?php

namespace Tests\Feature\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateRefreeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid Parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'hired_at' => today()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->get(route('referees.edit', $referee));

        $response->assertViewIs('referees.edit');
        $this->assertTrue($response->data('referee')->is($referee));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();

        $response = $this->get(route('referees.edit', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_referee()
    {
        $referee = factory(Referee::class)->create();

        $response = $this->get(route('referees.edit', $referee));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_a_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams());

        $response->assertRedirect(route('referees.index'));
        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('John', $referee->first_name);
            $this->assertEquals('Smith', $referee->last_name);
            $this->assertEquals(today()->toDateTimeString(), $referee->hired_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_referee()
    {
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_referee_first_name_is_required()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams([
            'first_name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('first_name');
    }

    /** @test */
    public function a_referee_last_name_is_required()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams([
            'last_name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('last_name');
    }

    /** @test */
    public function a_referee_hired_at_date_is_required()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams([
            'hired_at' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_referee_hired_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams([
            'hired_at' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_referee_hired_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams([
            'hired_at' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }
}
