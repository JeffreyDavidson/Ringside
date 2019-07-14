<?php

namespace Tests\Feature\SuperAdmin\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group superadmins
 */
class ViewTitlePageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_view_a_title_page()
    {
        $this->actAs('super-administrator');
        $title = factory(Title::class)->create();

        $response = $this->get(route('titles.show', ['title' => $title]));

        $response->assertViewIs('titles.show');
        $this->assertTrue($response->data('title')->is($title));
    }
}
