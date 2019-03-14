<?php

namespace Tests\Feature\Titles;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateTitleTest extends TestCase
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
            'name' => 'Example Title Name',
            'introduced_at' => today()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_title()
    {
        $this->actAs('administrator');

        $response = $this->get(route('titles.create'));

        $response->assertViewIs('titles.create');
    }

    /** @test */
    public function a_basic_user_can_view_the_form_for_creating_a_title()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('titles.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_title()
    {
        $response = $this->get(route('titles.create'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_create_a_title()
    {
        $this->withoutExceptionHandling();
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams());

        $response->assertRedirect(route('titles.index'));
        tap(Title::first(), function ($title) {
            $this->assertEquals('Example Title Name', $title->name);
        });
    }

    /** @test */
    public function a_title_slug_is_generated_when_created()
    {
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams());

        tap(Title::first(), function ($title) {
            $this->assertEquals('example-title-name', $title->slug);
        });
    }

    /** @test */
    public function a_title_introduced_today_or_before_is_active()
    {
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams(['introduced_at' => today()->toDateTimeString()]));

        tap(Title::first(), function ($title) {
            $this->assertTrue($title->is_active);
        });
    }

    /** @test */
    public function a_title_introduced_after_today_is_inactive()
    {
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams(['introduced_at' => Carbon::tomorrow()->toDateTimeString()]));

        tap(Title::first(), function ($title) {
            $this->assertFalse($title->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_title()
    {
        $this->actAs('basic-user');

        $response = $this->post(route('titles.store'), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_create_a_title()
    {
        $response = $this->post(route('titles.store'), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_title_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams(['name' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_title_introduced_at_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams(['introduced_at' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('introduced_at');
    }

    /** @test */
    public function a_title_introduced_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams(['introduced_at' => today()->toDateString()]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('introduced_at');
    }

    /** @test */
    public function a_title_introduced_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams(['introduced_at' => 'not-a-datetime']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('introduced_at');
    }
}
