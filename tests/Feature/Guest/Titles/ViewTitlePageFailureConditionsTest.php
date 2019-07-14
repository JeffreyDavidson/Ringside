<?php

namespace Tests\Feature\Guest\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group guests
 */
class ViewTitlePageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_a_title()
    {
        $title = factory(Title::class)->create();

        $response = $this->get(route('titles.show', ['title' => $title]));

        $response->assertRedirect(route('login'));
    }
}
