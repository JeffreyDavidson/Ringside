<?php

namespace Tests\Feature\Titles;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** @group titles */
class UpdateTitleTest extends TestCase
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
            'name' => 'Example Name Title',
            'introduced_at' => today()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->get(route('titles.edit', $title));

        $response->assertViewIs('titles.edit');
        $this->assertTrue($response->data('title')->is($title));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->create();

        $response = $this->get(route('titles.edit', $title));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_title()
    {
        $title = factory(Title::class)->create();

        $response = $this->get(route('titles.edit', $title));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_a_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams());

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) {
            $this->assertEquals('Example Name Title', $title->name);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_title()
    {
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_title_name_is_required()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams([
            'name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_title_name_must_contain_at_least_3_characters()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams([
            'name' => 'ab'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_title_name_must_end_with_title_or_titles()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams([
            'name' => 'Example Name'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_title_name_must_be_unique()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create(['name' => 'Example One Title']);
        factory(Title::class)->create(['name' => 'Example Two Title']);

        $response = $this->patch(route('titles.update', $title), $this->validParams([
            'name' => 'Example Two Title'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_title_introduced_at_date_is_required()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams([
            'introduced_at' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('introduced_at');
    }

    /** @test */
    public function a_title_introduced_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams([
            'introduced_at' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('introduced_at');
    }

    /** @test */
    public function a_title_introduced_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.update', $title), $this->validParams([
            'introduced_at' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('introduced_at');
    }

    /** @test */
    public function a_title_that_has_been_introduced_in_the_past_must_be_introduced_before_or_on_same_day()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create(['introduced_at' => Carbon::yesterday()]);

        $response = $this->patch(route('titles.update', $title), $this->validParams([
            'introduced_at' => Carbon::today()->addDays(3)->toDateTimeString(),
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('introduced_at');
    }
}
