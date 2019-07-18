<?php

namespace Tests\Feature\Guest\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group guests
 */
class ActivateWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_activate_a_pending_introduced_wrestler()
    {
        $wrestler = factory(Wrestler::class)->states('pending-introduced')->create();

        $response = $this->put(route('wrestlers.activate', $wrestler));

        $response->assertRedirect(route('login'));
    }
}
