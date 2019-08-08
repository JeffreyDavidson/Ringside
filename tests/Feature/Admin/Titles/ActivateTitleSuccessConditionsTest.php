<?php

namespace Tests\Feature\Admin\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group admins
 */
class ActivateTitleSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_a_pending_introduction_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('pending-introduction')->create();

        $response = $this->put(route('titles.activate', $title));

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) {
            $this->assertTrue($title->is_bookable);
        });
    }
}
