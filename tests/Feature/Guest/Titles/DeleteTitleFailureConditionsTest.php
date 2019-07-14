<?php

namespace Tests\Feature\Guests\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group guests
 */
class DeleteTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_delete_a_title()
    {
        $title = factory(Title::class)->create();

        $response = $this->delete(route('titles.destroy', $title));

        $response->assertRedirect(route('login'));
    }
}
