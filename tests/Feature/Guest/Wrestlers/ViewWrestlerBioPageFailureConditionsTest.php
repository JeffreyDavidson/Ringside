<?php

namespace Tests\Feature\Guest\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group guests
 */
class ViewWrestlerBioPageFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_a_wrestler_profile()
    {
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->get(route('wrestlers.show', ['wrestler' => $wrestler]));

        $response->assertRedirect(route('login'));
    }
}
