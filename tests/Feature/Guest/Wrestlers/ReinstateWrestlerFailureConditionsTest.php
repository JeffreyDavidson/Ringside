<?php

namespace Tests\Feature\Guest\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group guests
 */
class ReinstateWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_reinstate_a_suspended_wrestler()
    {
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->put(route('wrestlers.reinstate', $wrestler));

        $response->assertRedirect(route('login'));
    }
}
