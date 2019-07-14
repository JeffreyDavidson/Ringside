<?php

namespace Tests\Feature\User\Titles;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group users
 */
class ViewTitlesListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_titles_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('titles.index'));

        $response->assertForbidden();
    }
}
