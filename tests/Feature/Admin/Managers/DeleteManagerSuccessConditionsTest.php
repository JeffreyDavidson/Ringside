<?php

namespace Tests\Feature\Admin\Managers;

use Tests\TestCase;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group admins
 */
class DeleteManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_bookable_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->delete(route('managers.destroy', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertSoftDeleted('managers', [
            'id' => $manager->id,
            'first_name' => $manager->first_name,
            'last_name' => $manager->last_name
        ]);
    }

    /** @test */
    public function an_administrator_can_delete_a_pending_introduction_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('pending-introduction')->create();

        $response = $this->delete(route('managers.destroy', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertSoftDeleted('managers', [
            'id' => $manager->id,
            'first_name' => $manager->first_name,
            'last_name' => $manager->last_name
        ]);
    }

    /** @test */
    public function an_administrator_can_delete_a_retired_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->delete(route('managers.destroy', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertSoftDeleted('managers', [
            'id' => $manager->id,
            'first_name' => $manager->first_name,
            'last_name' => $manager->last_name
        ]);
    }

    /** @test */
    public function an_administrator_can_delete_a_suspended_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->delete(route('managers.destroy', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertSoftDeleted('managers', [
            'id' => $manager->id,
            'first_name' => $manager->first_name,
            'last_name' => $manager->last_name
        ]);
    }

    /** @test */
    public function an_administrator_can_delete_an_injured_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->delete(route('managers.destroy', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertSoftDeleted('managers', [
            'id' => $manager->id,
            'first_name' => $manager->first_name,
            'last_name' => $manager->last_name
        ]);
    }
}
