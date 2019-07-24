<?php

namespace Tests\Feature\Generic\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 */
class ViewManagerBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_managers_data_can_be_seen_on_their_profile()
    {
        $this->actAs('administrator');

        $manager = factory(Manager::class)->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        $response = $this->get(route('managers.show', $manager));

        $response->assertSee('John Smith');
    }
}
