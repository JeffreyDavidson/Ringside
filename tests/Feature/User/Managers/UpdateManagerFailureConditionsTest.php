<?php

namespace Tests\Feature\User\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group users
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
    public function a_basic_user_cannot_view_the_form_for_editing_a_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create();

        $response = $this->get(route('managers.edit', $manager));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_manager()
    {
        $this->actAs('basic-user');
        $manager = factory(Manager::class)->create();

        $response = $this->patch(route('managers.update', $manager), $this->validParams());

        $response->assertForbidden();
    }
}
