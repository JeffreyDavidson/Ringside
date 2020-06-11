<?php

namespace Tests\Feature\Stables;

use App\Enums\Role;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 */
class CreateStableFailureConditionsTest extends TestCase
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
        $wrestler = WrestlerFactory::new()->bookable()->create();
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestler->getKey()],
            'tagteams' => [$tagTeam->getKey()],
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_stable()
    {
        $this->actAs(Role::BASIC);

        $response = $this->createRequest('stables.create');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_stable()
    {
        $this->actAs(Role::BASIC);

        $response = $this->storeRequest('stables', $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_stable()
    {
        $response = $this->createRequest('stables');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_stable()
    {
        $response = $this->storeRequest('stables', $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_stable_name_is_required()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('stables.create'))
                        ->post(route('stables.store'), $this->validParams([
                            'name' => '',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('stables.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_name_must_be_a_string()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('stables.create'))
                        ->post(route('stables.store'), $this->validParams([
                            'name' => ['not-a-string'],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('stables.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_name_must_be_unique()
    {
        $this->actAs(Role::ADMINISTRATOR);
        factory(Stable::class)->create(['name' => 'Example Stable Name']);

        $response = $this->from(route('stables.create'))
                        ->post(route('stables.store'), $this->validParams([
                            'name' => 'Example Stable Name',
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('stables.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, Stable::count());
    }

    /** @test */
    public function a_stable_started_at_date_is_must_be_a_string_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('stables.create'))
                        ->post(route('stables.store'), $this->validParams([
                            'started_at' => ['not-a-string'],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('stables.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_started_at_must_be_in_datetime_format_if_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->from(route('stables.create'))
                        ->post(route('stables.store'), $this->validParams([
                            'started_at' => now()->toDateString(),
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('stables.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_with_one_wrestler_requires_a_tag_team()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->from(route('stables.create'))
                        ->post(route('stables.store'), $this->validParams([
                            'wrestlers' => [$wrestler->getKey()],
                            'tagteams' => [],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('stables.create'));
        $response->assertSessionHasErrors('tagteams');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_with_one_tag_team_requires_a_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->from(route('stables.create'))
                        ->post(route('stables.store'), $this->validParams([
                            'tagteams' => [$tagTeam->getKey()],
                            'wrestlers' => [],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('stables.create'));
        $response->assertSessionHasErrors('wrestlers');
        $this->assertEquals(0, Stable::count());
    }
}
