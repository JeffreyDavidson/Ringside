<?php

namespace Tests\Feature\Guest\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group guests
 * @group roster
 */
class InjureManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_injure_a_bookable_manager()
    {
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->injureRequest($manager);

        $response->assertRedirect(route('login'));
    }
}
