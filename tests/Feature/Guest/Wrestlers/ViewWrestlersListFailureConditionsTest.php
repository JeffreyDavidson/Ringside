<?php

namespace Tests\Feature\Guest\Wrestlers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group guests
 */
class ViewWrestlersListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_wrestlers_page()
    {
        $response = $this->get(route('wrestlers.index'));

        $response->assertRedirect(route('login'));
    }
}
