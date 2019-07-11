<?php

namespace Tests\Feature\Guest\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group guests
 */
class SuspendWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_suspend_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->put(route('wrestlers.suspend', $wrestler));

        $response->assertRedirect(route('login'));
    }
}
