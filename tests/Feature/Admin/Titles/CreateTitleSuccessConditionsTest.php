<?php

namespace Tests\Feature\Admin\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group admins
 */
class CreateTitleSucessConditionsTest extends TestCase
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
    public function an_administrator_can_view_the_form_for_creating_a_title()
    {
        $this->actAs('administrator');

        $response = $this->get(route('titles.create'));

        $response->assertViewIs('titles.create');
        $response->assertViewHas('title', new Title);
    }

    /** @test */
    public function an_administrator_can_create_a_title()
    {
        $this->actAs('administrator');

        $response = $this->post(route('titles.store'), $this->validParams());

        $response->assertRedirect(route('titles.index'));
        tap(Title::first(), function ($title) {
            $this->assertEquals('Example Name Title', $title->name);
            $this->assertEquals(now()->toDateTimeString(), $title->introduced_at->toDateTimeString());
        });
    }
}
