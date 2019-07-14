<?php

namespace Tests\Feature\Guest\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group guests
 */
class RestoreTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_restore_a_deleted_title()
    {
        $title = factory(Title::class)->create(['deleted_at' => now()->toDateTimeString()]);

        $response = $this->put(route('titles.restore', $title));

        $response->assertRedirect(route('login'));
    }
}
