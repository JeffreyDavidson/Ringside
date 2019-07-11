<?php

namespace Tests\Feature\Generic\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generics
 */
class UpdateWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Default attributes for model.
     *
     * @param  array  $overrides
     * @return array
     */
    private function oldAttributes($overrides = [])
    {
        return array_replace([
            'name' => 'Old Wrestler Name',
            'height' => 73,
            'weight' => 240,
            'hometown' => 'Old City, State',
            'signature_move' => 'Old Finisher',
            'hired_at' => today()->toDateString(),
        ], $overrides);
    }

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
            'hired_at' => today()->toDateString(),
        ], $overrides);
    }

    /** @test */
    public function a_wrestler_name_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'name' => '',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('name');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Old Wrestler Name', $wrestler->name);
        });
    }

    /** @test */
    public function a_wrestler_name_must_be_a_string()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'name' => ['not-a-string'],
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('name');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Old Wrestler Name', $wrestler->name);
        });
    }

    /** @test */
    public function a_wrestler_name_must_be_at_least_three_characters()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'name' => 'Ab',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('name');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Old Wrestler Name', $wrestler->name);
        });
    }

    /** @test */
    public function a_wrestler_height_in_feet_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'feet' => '',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('feet');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(6, $wrestler->feet);
        });
    }

    /** @test */
    public function a_wrestler_height_in_feet_must_be_numeric()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'feet' => 'not-an-integer',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('feet');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(6, $wrestler->feet);
        });
    }

    /** @test */
    public function a_wrestler_height_in_feet_must_be_a_minimum_of_five()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'feet' => '4',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('feet');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(6, $wrestler->feet);
        });
    }

    /** @test */
    public function a_wrestler_height_in_feet_must_be_a_maximum_of_seven()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'feet' => '8',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('feet');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(6, $wrestler->feet);
        });
    }

    /** @test */
    public function a_wrestler_height_in_inches_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'inches' => '',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('inches');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(1, $wrestler->inches);
        });
    }

    /** @test */
    public function a_wrestler_height_in_inches_is_must_be_numeric()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'inches' => 'not-an-integer',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('inches');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(1, $wrestler->inches);
        });
    }

    /** @test */
    public function a_wrestler_height_in_inches_must_be_less_than_twelve()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'inches' => 12,
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('inches');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(1, $wrestler->inches);
        });
    }

    /** @test */
    public function a_wrestler_weight_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'weight' => '',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('weight');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(240, $wrestler->weight);
        });
    }

    /** @test */
    public function a_wrestler_weight_must_be_numeric()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'weight' => 'not-an-integer',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('weight');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(240, $wrestler->weight);
        });
    }

    /** @test */
    public function a_wrestler_hometown_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'hometown' => '',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('hometown');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Old City, State', $wrestler->hometown);
        });
    }

    /** @test */
    public function a_wrestler_hometown_must_be_a_string()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'hometown' => ['not-a-string'],
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('hometown');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Old City, State', $wrestler->hometown);
        });
    }

    /** @test */
    public function a_wrestler_signature_move_must_be_a_string_if_present()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'signature_move' => ['not-a-string'],
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('signature_move');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('Old Finisher', $wrestler->signature_move);
        });
    }

    /** @test */
    public function a_wrestler_hired_at_date_is_required()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'hired_at' => '',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('hired_at');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(today()->toDateString(), $wrestler->hired_at->toDateString());
        });
    }

    /** @test */
    public function a_wrestler_hired_at_date_must_be_a_string()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'hired_at' => ['not-a-string'],
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('hired_at');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(today()->toDateString(), $wrestler->hired_at->toDateString());
        });
    }

    /** @test */
    public function a_wrestler_hired_at_must_be_in_date_format()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'hired_at' => 'not-a-date-format',
                        ]));

        $response->assertRedirect(route('wrestlers.edit', $wrestler));
        $response->assertSessionHasErrors('hired_at');
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals(today()->toDateString(), $wrestler->hired_at->toDateString());
        });
    }
}
