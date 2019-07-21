<?php

namespace Tests\Feature\Guest\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group guests
 */
class RetireTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_retire_a_bookable_title()
    {
        $title = factory(Title::class)->states('bookable')->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertRedirect(route('login'));
    }
}