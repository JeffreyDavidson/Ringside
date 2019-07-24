<?php

namespace Tests\Feature\Admin\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group admins
 */
class RecoverManagerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_recover_an_injured_manager()
    {
        $this->actAs('administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->put(route('managers.recover', $manager));

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals(now()->toDateTimeString(), $manager->fresh()->injuries()->latest()->first()->ended_at);
    }
}
