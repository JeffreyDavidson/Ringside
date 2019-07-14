<?php

namespace Tests\Feature\User\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group users
 */
class RestoreTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->create(['deleted_at' => now()->toDateTimeString()]);

        $response = $this->put(route('titles.restore', $title));

        $response->assertForbidden();
    }
}
