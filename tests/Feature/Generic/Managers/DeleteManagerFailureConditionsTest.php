<?php

namespace Tests\Feature\Generic\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group generics
 */
class DeleteManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_already_deleted_manager_cannot_be_deleted()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->create()->delete();

        $response = $this->delete(route('managers.destroy', $manager));

        $response->assertNotFound();
    }
}
