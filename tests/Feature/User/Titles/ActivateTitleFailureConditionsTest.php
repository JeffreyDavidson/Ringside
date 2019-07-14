<?php

namespace Tests\Feature\User\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group users
 */
class ActivateTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_activate_a_pending_introduced_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->states('pending-introduced')->create();

        $response = $this->put(route('titles.activate', $title));

        $response->assertForbidden();
    }
}
