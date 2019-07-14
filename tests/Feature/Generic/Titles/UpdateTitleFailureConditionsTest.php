<?php

namespace Tests\Feature\Generic\Titles;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group generics
 */
class UpdateTitleFailureConditionsTest extends TestCase
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
            'name' => 'Old Title Name',
            'introduced_at' => now()->toDateTimeString(),
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
            'name' => 'Example Name Title',
            'introduced_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_title_name_is_required()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create($this->oldAttributes());

        $response = $this->from(route('titles.edit', $title))
                        ->patch(route('titles.update', $title), $this->validParams([
                            'name' => ''
                        ]));

        $response->assertRedirect(route('titles.edit', $title));
        $response->assertSessionHasErrors('name');
        tap($title->fresh(), function ($title) {
            $this->assertEquals('Old Title Name', $title->name);
        });
    }

    /** @test */
    public function a_title_name_must_contain_at_least_3_characters()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create($this->oldAttributes());

        $response = $this->from(route('titles.edit', $title))
                        ->patch(route('titles.update', $title), $this->validParams([
                            'name' => 'ab'
                        ]));

        $response->assertRedirect(route('titles.edit', $title));
        $response->assertSessionHasErrors('name');
        tap($title->fresh(), function ($title) {
            $this->assertEquals('Old Title Name', $title->name);
        });
    }

    /** @test */
    public function a_title_name_must_end_with_title_or_titles()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create($this->oldAttributes());

        $response = $this->from(route('titles.edit', $title))
                        ->patch(route('titles.update', $title), $this->validParams([
                            'name' => 'Example Name'
                        ]));

        $response->assertRedirect(route('titles.edit', $title));
        $response->assertSessionHasErrors('name');
        tap($title->fresh(), function ($title) {
            $this->assertEquals('Old Title Name', $title->name);
        });
    }

    /** @test */
    public function a_title_name_must_be_unique()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create($this->oldAttributes(['name' => 'Example One Title']));
        factory(Title::class)->create(['name' => 'Example Two Title']);

        $response = $this->from(route('titles.edit', $title))
                        ->patch(route('titles.update', $title), $this->validParams([
                            'name' => 'Example Two Title'
                        ]));

        $response->assertRedirect(route('titles.edit', $title));
        $response->assertSessionHasErrors('name');
        tap($title->fresh(), function ($title) {
            $this->assertEquals('Example One Title', $title->name);
            ;
        });
    }

    /** @test */
    public function a_title_introduced_at_date_is_required()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create($this->oldAttributes());

        $response = $this->from(route('titles.edit', $title))
                        ->patch(route('titles.update', $title), $this->validParams([
                            'introduced_at' => ''
                        ]));

        $response->assertRedirect(route('titles.edit', $title));
        $response->assertSessionHasErrors('introduced_at');
        tap($title->fresh(), function ($title) {
            $this->assertEquals(now()->toDateTimeString(), $title->introduced_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_title_introduced_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create($this->oldAttributes());

        $response = $this->from(route('titles.edit', $title))
                        ->patch(route('titles.update', $title), $this->validParams([
                            'introduced_at' => now()->toDateString()
                        ]));

        $response->assertRedirect(route('titles.edit', $title));
        $response->assertSessionHasErrors('introduced_at');
        tap($title->fresh(), function ($title) {
            $this->assertEquals(now()->toDateTimeString(), $title->introduced_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_title_introduced_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create($this->oldAttributes());

        $response = $this->from(route('titles.edit', $title))
                        ->patch(route('titles.update', $title), $this->validParams([
                            'introduced_at' => 'not-a-datetime'
                        ]));

        $response->assertRedirect(route('titles.edit', $title));
        $response->assertSessionHasErrors('introduced_at');
        tap($title->fresh(), function ($title) {
            $this->assertEquals(today()->toDateString(), $title->introduced_at->toDateString());
        });
    }

    /** @test */
    public function a_title_that_has_been_introduced_in_the_past_must_be_introduced_before_or_on_same_day()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create($this->oldAttributes(['introduced_at' => Carbon::yesterday()->toDateTimeString()]));

        $response = $this->from(route('titles.edit', $title))
                        ->patch(route('titles.update', $title), $this->validParams([
                            'introduced_at' => now()->addDays(3)->toDateTimeString(),
                        ]));

        $response->assertRedirect(route('titles.edit', $title));
        $response->assertSessionHasErrors('introduced_at');
        tap($title->fresh(), function ($title) {
            $this->assertEquals(Carbon::yesterday()->toDateTimeString(), $title->introduced_at->toDateTimeString());
        });
    }
}
