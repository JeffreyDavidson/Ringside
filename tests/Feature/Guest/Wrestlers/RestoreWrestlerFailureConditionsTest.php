<?php

namespace Tests\Feature\Guest\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_restore_a_deleted_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create(['deleted_at' => now()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertRedirect(route('login'));
    }
}
