<?php

namespace Tests\Feature\Wrestlers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateWrestlerTest extends TestCase
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
            'feet' => '6',
            'inches' => '4',
            'weight' => '240',
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
            'hired_at' => today()->toDateString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_wrestler()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->get(route('wrestlers.create'));

        $response->assertViewIs('wrestlers.create');
        $response->assertViewHas('wrestler', new Wrestler);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_wrestler()
    {
        $user = factory(User::class)->states('basic-user')->create();

        $response = $this->actingAs($user)->get(route('wrestlers.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_wrestler()
    {
        $response = $this->get(route('wrestlers.create'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_create_a_wrestler()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams());

        $response->assertRedirect(route('wrestlers.index'));
        tap(Wrestler::first(), function ($wrestler) {
            $this->assertEquals('Example Wrestler Name', $wrestler->name);
            $this->assertEquals('example-wrestler-name', $wrestler->slug);
            $this->assertEquals(76, $wrestler->height);
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('The Finisher', $wrestler->signature_move);
            $this->assertEquals(today()->toDateString(), $wrestler->hired_at->toDateString());
        });
    }

    /** @test */
    public function a_wrestler_hired_today_or_before_is_active()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'hired_at' => today()->toDateTimeString()
                        ]));

        tap(Wrestler::first(), function ($wrestler) {
            $this->assertTrue($wrestler->is_active);
        });
    }

    /** @test */
    public function a_wrestler_hired_after_today_is_inactive()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'hired_at' => Carbon::tomorrow()->toDateTimeString()
                        ]));

        tap(Wrestler::first(), function ($wrestler) {
            $this->assertFalse($wrestler->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_wrestler()
    {
        $user = factory(User::class)->states('basic-user')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_create_a_wrestler()
    {
        $response = $this->post(route('wrestlers.store'), $this->validParams());

        $response->assertRedirect('/login');
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
    public function a_wrestler_signature_move_is_optional()
    {
        $user = factory(User::class)->states('administrator')->create();

        $response = $this->actingAs($user)
                        ->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams([
                            'signature_move' => '',
                        ]));

        $response->assertSessionDoesntHaveErrors('signature_move');
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
