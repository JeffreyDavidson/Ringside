<?php

namespace Tests\Feature\Guest\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group guests
 */
class ViewStableBioPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_a_stable_profile()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('stables.show', $stable));

        $response->assertRedirect(route('login'));
    }
}
