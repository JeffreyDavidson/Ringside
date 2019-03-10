<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateStableTest extends TestCase
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
        $wrestler = factory(Wrestler::class)->states('active')->create();
        $tagteam = factory(TagTeam::class)->states('active')->create();

        return array_replace_recursive([
            'name' => 'Example Stable Name',
            'started_at' => today()->toDateTimeString(),
            'wrestlers' => [$wrestler->getKey()],
            'tagteams' => [$tagteam->getKey()],
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_stable()
    {
        $this->actAs('administrator');

        $response = $this->get(route('stables.create'));

        $response->assertViewIs('stables.create');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_stable()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('stables.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_stable()
    {
        $response = $this->get(route('stables.create'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_create_a_stable()
    {
        $this->actAs('administrator');

        $response = $this->post(route('stables.store'), $this->validParams());

        $response->assertRedirect(route('stables.index'));
        tap(Stable::first(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
            $this->assertEquals(today()->toDateTimeString(), $stable->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function wrestlers_are_added_to_stable_if_present()
    {
        $this->actAs('administrator');
        $createdWrestlers = factory(Wrestler::class, 3)->states('active')->create();

        $response = $this->post(route('stables.store'), $this->validParams([
            'wrestlers' => $createdWrestlers->modelKeys()
        ]));

        tap(Stable::first()->wrestlers, function ($wrestlers) use ($createdWrestlers) {
            $this->assertCount(3, $wrestlers);
            $this->assertEquals($wrestlers->modelKeys(), $createdWrestlers->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_are_added_to_stable_if_present()
    {
        $this->actAs('administrator');
        $createdTagTeams = factory(TagTeam::class, 3)->states('active')->create();

        $response = $this->post(route('stables.store'), $this->validParams([
            'tagteams' => $createdTagTeams->modelKeys()
        ]));

        tap(Stable::first()->tagteams, function ($tagteams) use ($createdTagTeams) {
            $this->assertCount(3, $tagteams);
            $this->assertEquals($tagteams->modelKeys(), $createdTagTeams->modelKeys());
        });
    }

    /** @test */
    public function a_stable_slug_is_generated_when_created()
    {
        $this->actAs('administrator');

        $response = $this->post(route('stables.store'), $this->validParams());

        tap(Stable::first(), function ($stable) {
            $this->assertEquals('example-stable-name', $stable->slug);
        });
    }

    /** @test */
    public function a_stable_started_today_or_before_is_active()
    {
        $this->actAs('administrator');

        $response = $this->post(route('stables.store'), $this->validParams([
            'started_at' => today()->toDateTimeString()
        ]));

        tap(Stable::first(), function ($stable) {
            $this->assertTrue($stable->is_active);
        });
    }

    /** @test */
    public function a_stable_started_after_today_is_inactive()
    {
        $this->actAs('administrator');

        $response = $this->post(route('stables.store'), $this->validParams([
            'started_at' => Carbon::tomorrow()->toDateTimeString()
        ]));

        tap(Stable::first(), function ($stable) {
            $this->assertFalse($stable->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_stable()
    {
        $this->actAs('basic-user');

        $response = $this->post(route('stables.store'), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_create_a_stable()
    {
        $response = $this->post(route('stables.store'), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_stable_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('stables.store'), $this->validParams([
            'name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_stable_started_at_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('stables.store'), $this->validParams([
            'started_at' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function a_stable_started_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('stables.store'), $this->validParams([
            'started_at' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function a_stable_started_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('stables.store'), $this->validParams([
            'started_at' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('started_at');
    }
}
