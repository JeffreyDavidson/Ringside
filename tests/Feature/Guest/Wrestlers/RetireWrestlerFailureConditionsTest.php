<?php

namespace Tests\Feature\Guest\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group guests
 */
class RetireWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_retire_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->put(route('wrestlers.retire', $wrestler));

        $response->assertRedirect(route('login'));
    }
}
