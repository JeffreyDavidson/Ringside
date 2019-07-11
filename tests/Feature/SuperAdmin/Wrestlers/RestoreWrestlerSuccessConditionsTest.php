<?php

namespace Tests\Feature\SuperAdmin\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group superadmins
 */
class RestoreWrestlerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_restore_a_deleted_wrestler()
    {
        $this->actAs('super-administrator');
        $wrestler = factory(Wrestler::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->put(route('wrestlers.restore', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertNull($wrestler->fresh()->deleted_at);
    }
}
