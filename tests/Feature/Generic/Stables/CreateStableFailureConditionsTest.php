<?php

namespace Tests\Feature\Generic\Stables;

use Tests\TestCase;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group generics
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
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestler->getKey()],
            'tagteams' => [$tagteam->getKey()],
        ], $overrides);
    }

    /** @test */
    public function a_stable_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('roster.stables.create'))
                        ->post(route('roster.stables.store'), $this->validParams([
                            'name' => ''
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('roster.stables.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_name_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('roster.stables.create'))
                        ->post(route('roster.stables.store'), $this->validParams([
                            'name' => ['not-a-string']
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('roster.stables.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_name_must_be_unique()
    {
        $this->actAs('administrator');
        factory(Stable::class)->create(['name' => 'Example Stable Name']);

        $response = $this->from(route('roster.stables.create'))
                        ->post(route('roster.stables.store'), $this->validParams([
                            'name' => 'Example Stable Name'
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('roster.stables.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, Stable::count());
    }

    /** @test */
    public function a_stable_started_at_date_is_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('roster.stables.create'))
                        ->post(route('roster.stables.store'), $this->validParams([
                            'started_at' => ['not-a-string']
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('roster.stables.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_started_at_must_be_in_datetime_format_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('roster.stables.create'))
                        ->post(route('roster.stables.store'), $this->validParams([
                            'started_at' => now()->toDateString()
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('roster.stables.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_with_one_wrestler_requires_a_tag_team()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->from(route('roster.stables.create'))
                        ->post(route('roster.stables.store'), $this->validParams([
                            'wrestlers' => [$wrestler->getKey()],
                            'tagteams' => [],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('roster.stables.create'));
        $response->assertSessionHasErrors('tagteams');
        $this->assertEquals(0, Stable::count());
    }

    /** @test */
    public function a_stable_with_one_tag_team_requires_a_wrestler()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->from(route('roster.stables.create'))
                        ->post(route('roster.stables.store'), $this->validParams([
                            'tagteams' => [$tagteam->getKey()],
                            'wrestlers' => [],
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('roster.stables.create'));
        $response->assertSessionHasErrors('wrestlers');
        $this->assertEquals(0, Stable::count());
    }
}
