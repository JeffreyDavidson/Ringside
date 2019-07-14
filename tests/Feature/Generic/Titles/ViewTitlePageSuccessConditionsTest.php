<?php

namespace Tests\Feature\Generic\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group generics
 */
class ViewTitlePageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_titles_data_can_be_seen_on_the_title_page()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create(['name' => 'Title 1']);

        $response = $this->get(route('titles.show', ['title' => $title]));

        $response->assertSee('Title 1');
    }
}
