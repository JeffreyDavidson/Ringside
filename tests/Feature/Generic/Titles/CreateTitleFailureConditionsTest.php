<?php

namespace Tests\Feature\Generic\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group generics
 */
class CreateTitleFailureConditionsTest extends TestCase
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
            'name' => 'Example Name Title',
            'introduced_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_title_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('titles.create'))
                        ->post(route('titles.store'), $this->validParams(['name' => '']));

        $response->assertStatus(302);
        $response->assertRedirect(route('titles.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Title::count());
    }

    /** @test */
    public function a_title_name_must_contain_at_least_three_characters()
    {
        $this->actAs('administrator');

        $response = $this->from(route('titles.create'))
                        ->post(route('titles.store'), $this->validParams(['name' => 'ab']));

        $response->assertStatus(302);
        $response->assertRedirect(route('titles.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Title::count());
    }

    /** @test */
    public function a_title_name_must_end_with_title_or_titles()
    {
        $this->actAs('administrator');

        $response = $this->from(route('titles.create'))
                        ->post(route('titles.store'), $this->validParams(['name' => 'Example Name']));

        $response->assertStatus(302);
        $response->assertRedirect(route('titles.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Title::count());
    }

    /** @test */
    public function a_title_name_must_be_unique()
    {
        $this->actAs('administrator');
        factory(Title::class)->create(['name' => 'Example Title']);

        $response = $this->from(route('titles.create'))
                        ->post(route('titles.store'), $this->validParams(['name' => 'Example Title']));

        $response->assertStatus(302);
        $response->assertRedirect(route('titles.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, Title::count());
    }

    /** @test */
    public function a_title_introduced_at_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('titles.create'))
                        ->post(route('titles.store'), $this->validParams(['introduced_at' => '']));

        $response->assertStatus(302);
        $response->assertRedirect(route('titles.create'));
        $response->assertSessionHasErrors('introduced_at');
        $this->assertEquals(0, Title::count());
    }

    /** @test */
    public function a_title_introduced_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->from(route('titles.create'))
                        ->post(route('titles.store'), $this->validParams(['introduced_at' => now()->toDateString()]));

        $response->assertStatus(302);
        $response->assertRedirect(route('titles.create'));
        $response->assertSessionHasErrors('introduced_at');
        $this->assertEquals(0, Title::count());
    }

    /** @test */
    public function a_title_introduced_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->from(route('titles.create'))
                        ->post(route('titles.store'), $this->validParams(['introduced_at' => 'not-a-datetime']));

        $response->assertStatus(302);
        $response->assertRedirect(route('titles.create'));
        $response->assertSessionHasErrors('introduced_at');
        $this->assertEquals(0, Title::count());
    }
}
