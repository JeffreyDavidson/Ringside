<?php

namespace Tests\Feature\Guest\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group guests
 */
class ActivateTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_activate_a_pending_introduced_title()
    {
        $title = factory(Title::class)->states('pending-introduced')->create();

        $response = $this->put(route('titles.activate', $title));

        $response->assertRedirect(route('login'));
    }
}
