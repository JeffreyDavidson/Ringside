<?php

namespace Tests\Feature\Admin\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group admins
 */
class RestoreWrestlerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->put(route('wrestlers.restore', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertNull($wrestler->fresh()->deleted_at);
    }
}
