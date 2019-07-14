<?php

namespace Tests\Feature\Guest\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group guests
 */
class UnretireTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_unretire_a_retired_title()
    {
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->put(route('titles.unretire', $title));

        $response->assertRedirect(route('login'));
    }
}
