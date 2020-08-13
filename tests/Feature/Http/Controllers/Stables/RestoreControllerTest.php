<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\StableFactory;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_restores_a_stable_and_redirects($administrators)
    {
        $this->markTestIncomplete();
        $this->actAs($administrators);
        $stable = StableFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($stable);

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) {
            $this->assertEquals(StableStatus::UNACTIVATED, $stable->status);
            $this->assertNull($stable->fresh()->deleted_at);
            $this->assertTrue($stable->previousMembers->every->is_bookable);
        });
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->softDeleted()->create();

        $this->restoreRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_stable()
    {
        $stable = StableFactory::new()->softDeleted()->create();

        $this->restoreRequest($stable)->assertRedirect(route('login'));
    }
}