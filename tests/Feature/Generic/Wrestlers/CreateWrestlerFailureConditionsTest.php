<?php

namespace Tests\Feature\Generic\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generics
 */
class CreateWrestlerFailureConditionsTest extends TestCase
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
            'name' => 'Example Wrestler Name',
            'feet' => '6',
            'inches' => '4',
            'weight' => '240',
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_wrestler_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'name' => '',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_name_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'name' => ['not-a-string'],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_name_must_be_at_least_three_characters()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'name' => 'Ab',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_feet_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'feet' => '',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('feet');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_feet_must_be_numeric()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'feet' => 'not-numeric',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('feet');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_feet_must_be_a_minimum_of_five()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'feet' => '4',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('feet');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_feet_must_be_a_maximum_of_seven()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'feet' => '8',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('feet');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_inches_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'inches' => '',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('inches');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_inches_is_must_be_numeric()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'inches' => 'not-numeric',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('inches');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_inches_must_be_less_than_twelve()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'inches' => '12',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('inches');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_weight_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'weight' => '',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('weight');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_weight_must_be_numeric()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'weight' => 'not-numeric',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('weight');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_hometown_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'hometown' => '',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('hometown');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_hometown_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'hometown' => ['not-a-string'],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('hometown');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_signature_move_must_be_a_string_if_present()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'signature_move' => ['not-a-string'],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('signature_move');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_started_at_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'started_at' => '',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_started_at_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'started_at' => ['not-a-date-format'],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_startd_at_must_be_in_date_format()
    {
        $this->actAs('administrator');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'started_at' => 'not-a-date-format',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Wrestler::count());
    }
}