<?php

namespace Tests\Feature\Guest\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group guests
 */
class UnretireManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_unretire_a_retired_manager()
    {
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->put(route('managers.unretire', $manager));

        $response->assertRedirect(route('login'));
    }
}
