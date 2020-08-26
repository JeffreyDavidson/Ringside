<?php

namespace Tests\Unit\Views\Titles;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NunoMaduro\LaravelMojito\InteractsWithViews;
use Tests\Factories\TitleFactory;
use Tests\TestCase;

/**
 * @group views
 */
class TitleBioTest extends TestCase
{
    use RefreshDatabase, InteractsWithViews;

    /** @test */
    public function a_titles_name_can_be_seen_on_the_title_page()
    {
        $title = TitleFactory::new()->create(['name' => 'Title 1']);

        $this->assertView('titles.show', compact('title'))->contains('Title 1');
    }
}
