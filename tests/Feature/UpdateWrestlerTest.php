<?php

namespace Tests\Feature;

use App\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateWrestlerTest extends TestCase
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
    public function an_administrator_can_view_the_form_for_editing_a_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->get(route('wrestlers.edit', $wrestler));

        $response->assertViewIs('wrestlers.edit');
        $this->assertTrue($response->data('wrestler')->is($wrestler));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->get(route('wrestlers.edit', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->get(route('wrestlers.edit', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_a_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams());

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals(76, $wrestler->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('The Finisher', $wrestler->signature_move);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_wrestler_name_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_wrestler_feet_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'feet' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('feet');
    }

    /** @test */
    public function a_wrestler_feet_must_be_an_integer()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'feet' => 'not-an-integer'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('feet');
    }

    /** @test */
    public function a_wrestler_inches_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'inches' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('inches');
    }

    /** @test */
    public function a_wrestler_inches_is_must_be_an_integer()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'inches' => 'not-an-integer'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('inches');
    }

    /** @test */
    public function a_wrestler_inches_is_must_be_less_than_or_equal_to_12()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'inches' => 13
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('inches');
    }

    /** @test */
    public function a_wrestler_weight_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'weight' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('weight');
    }

    /** @test */
    public function a_wrestler_weight_must_be_an_integer()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'weight' => 'not-an-integer'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('weight');
    }

    /** @test */
    public function a_wrestler_hometown_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'hometown' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hometown');
    }

    /** @test */
    public function a_wrestler_signature_move_is_optional()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'signature_move' => ''
        ]));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_wrestler_hired_at_date_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'hired_at' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_wrestler_hired_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'hired_at' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_wrestler_hired_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams([
            'hired_at' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }
}
