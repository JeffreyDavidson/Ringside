<?php

namespace Tests\Feature\Guest\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group guests
 */
class UpdateManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'started_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_manager()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->get(route('managers.edit', $manager));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_manager()
    {
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
