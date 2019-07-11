<?php

namespace Tests\Feature\Admin\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group admins
 */
class ActivateWrestlerSuccessCondtionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_an_inactive_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->put(route('wrestlers.activate', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertTrue($wrestler->is_bookable);
        });
    }
}
