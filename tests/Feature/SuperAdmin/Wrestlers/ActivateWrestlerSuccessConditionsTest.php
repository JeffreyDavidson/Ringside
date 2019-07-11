<?php

namespace Tests\Feature\SuperAdmin\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group superadmins
 */
class ActivateWrestlerSuccessCondtionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_activate_a_pending_introduced_wrestler()
    {
        $this->actAs('super-administrator');
        $wrestler = factory(Wrestler::class)->states('pending-introduced')->create();

        $response = $this->put(route('wrestlers.activate', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertTrue($wrestler->is_bookable);
        });
    }
}
