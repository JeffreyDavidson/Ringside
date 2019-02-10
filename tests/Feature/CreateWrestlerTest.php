<?php

namespace Tests\Feature;

use App\Wrestler;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid Parameters for reqeust.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Wrestler Name',
            'feet' => 6,
            'inches' => 4,
            'weight' => 240,
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
            'hired_at' => today()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_wrestler()
    {
        $this->actAs('administrator');

        $response = $this->get('/wrestlers/new');

        $response->assertViewIs('wrestlers.create');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_wrestler()
    {
        $this->actAs('basic-user');

        $response = $this->get('/wrestlers/new');

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_wrestler()
    {
        $response = $this->get('/wrestlers/new');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_create_a_wrestler()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams());

        $response->assertStatus(302);
        $response->assertRedirect('/wrestlers');
        tap(Wrestler::first(), function ($wrestler) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals(76, $wrestler->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('The Finisher', $wrestler->signature_move);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_wrestler()
    {
        $this->actAs('basic-user');

        $response = $this->post('/wrestlers', $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_create_a_wrestler()
    {
        $response = $this->post('/wrestlers', $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_wrestler_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['name' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_wrestler_feet_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['feet' => 'not-an-integer']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('feet');
    }

    /** @test */
    public function a_wrestler_feet_must_be_an_integer()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['feet' => 'not-an-integer']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('feet');
    }

    /** @test */
    public function a_wrestler_inches_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['inches' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('inches');
    }

    /** @test */
    public function a_wrestler_inches_is_must_be_an_integer()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['inches' => 'not-an-integer']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('inches');
    }

    /** @test */
    public function a_wrestler_inches_is_must_be_less_than_or_equal_to_12()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['inches' => 13]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('inches');
    }

    /** @test */
    public function a_wrestler_weight_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['weight' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('weight');
    }

    /** @test */
    public function a_wrestler_weight_must_be_an_integer()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['weight' => 'not-an-integer']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('weight');
    }

    /** @test */
    public function a_wrestler_hometown_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['hometown' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hometown');
    }

    /** @test */
    public function a_wrestler_signature_move_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['signature_move' => '']));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_wrestler_hired_at_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['hired_at' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_wrestler_hired_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['hired_at' => today()->toDateString()]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_wrestler_hired_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post('/wrestlers', $this->validParams(['hired_at' => 'not-a-datetime']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }
}
