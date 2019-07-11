<?php

namespace Tests\Feature\Guest\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group guests
 */
class DeleteWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_delete_a_wrestler()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('login'));
    }
}
