<?php

namespace Tests\Feature\SuperAdmin\Wrestlers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group superadmins
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
    public function an_administrator_can_view_the_form_for_editing_a_wrestler()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->get(route('wrestlers.edit', $wrestler));

        $response->assertViewIs('wrestlers.edit');
        $this->assertTrue($response->data('wrestler')->is($wrestler));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_wrestler()
    {
        $user = factory(User::class)->states('basic-user')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->get(route('wrestlers.edit', $wrestler));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->get(route('wrestlers.edit', $wrestler));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_a_wrestler()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams());

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
        $user = factory(User::class)->states('basic-user')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->patch(route('wrestlers.update', $wrestler), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_wrestler_name_is_required()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
    public function a_wrestler_feet_is_required()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
    public function a_wrestler_feet_must_be_numeric()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
    public function a_wrestler_feet_must_be_a_minimum_of_five()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
    public function a_wrestler_feet_must_be_a_maximum_of_seven()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
    public function a_wrestler_inches_is_required()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
    public function a_wrestler_inches_is_must_be_numeric()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
    public function a_wrestler_inches_must_be_less_than_twelve()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
    public function a_wrestler_signature_move_is_optional()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
                        ->patch(route('wrestlers.update', $wrestler), $this->validParams([
                            'signature_move' => '',
                        ]));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_wrestler_signature_move_must_be_a_string_if_present()
    {
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
        $user = factory(User::class)->states('administrator')->create();
        $wrestler = factory(Wrestler::class)->create($this->oldAttributes());

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.edit', $wrestler))
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
