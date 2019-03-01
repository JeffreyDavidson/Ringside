<?php

namespace Tests\Feature\Referees;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateRefereeTest extends TestCase
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
    public function an_administrator_can_view_the_form_for_creating_a_referee()
    {
        $this->actAs('administrator');

        $response = $this->get(route('referees.create'));

        $response->assertViewIs('referees.create');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_referee()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('referees.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_referee()
    {
        $response = $this->get(route('referees.create'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_create_a_referee()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams());

        $response->assertRedirect(route('referees.index'));
        tap(Referee::first(), function ($referee) {
            $this->assertEquals('John', $referee->first_name);
            $this->assertEquals('Smith', $referee->last_name);
            $this->assertEquals(today()->toDateTimeString(), $referee->hired_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_referee_slug_is_generated_when_created()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams());

        tap(Referee::first(), function ($referee) {
            $this->assertEquals('jsmith', $referee->slug);
        });
    }

    /** @test */
    public function a_referee_hired_today_or_before_is_active()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams([
            'hired_at' => today()->toDateTimeString()
        ]));

        tap(Referee::first(), function ($referee) {
            $this->assertTrue($referee->is_active);
        });
    }

    /** @test */
    public function a_referee_hired_after_today_is_inactive()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams([
            'hired_at' => Carbon::tomorrow()->toDateTimeString()
        ]));

        tap(Referee::first(), function ($referee) {
            $this->assertFalse($referee->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_referee()
    {
        $this->actAs('basic-user');

        $response = $this->post(route('referees.store'), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_create_a_referee()
    {
        $response = $this->post(route('referees.store'), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_referee_first_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams([
            'first_name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('first_name');
    }

    /** @test */
    public function a_referee_last_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams([
            'last_name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('last_name');
    }

    /** @test */
    public function a_referee_hired_at_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams([
            'hired_at' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_referee_hired_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams([
            'hired_at' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_referee_hired_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('referees.store'), $this->validParams([
            'hired_at' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }
}
