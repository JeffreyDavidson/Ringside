<?php

namespace Tests\Feature\Admin\Wrestlers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group admins
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
            'hired_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_wrestler_name_is_required()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
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
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'signature_move' => ['not-a-string'],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('signature_move');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_hired_at_date_is_required()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'hired_at' => '',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('hired_at');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_hired_at_must_be_a_string()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'hired_at' => ['not-a-date-format'],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('hired_at');
        $this->assertEquals(0, Wrestler::count());
    }

    /** @test */
    public function a_wrestler_hired_at_must_be_in_date_format()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'hired_at' => 'not-a-date-format',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('wrestlers.create'));
        $response->assertSessionHasErrors('hired_at');
        $this->assertEquals(0, Wrestler::count());
    }
}
