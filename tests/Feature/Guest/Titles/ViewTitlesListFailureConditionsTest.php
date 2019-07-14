<?php

namespace Tests\Feature\Guest\Titles;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group guests
 */
class ViewTitlesListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_titles_page()
    {
        $response = $this->get(route('titles.index'));

        $response->assertRedirect(route('login'));
    }
}
