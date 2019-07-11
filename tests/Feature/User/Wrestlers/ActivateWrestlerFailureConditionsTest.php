<?php

namespace Tests\Feature\User\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group users
 */
class ActivateWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_activate_a_pending_introduced_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->states('pending-introduced')->create();

        $response = $this->put(route('wrestlers.activate', $wrestler));

        $response->assertForbidden();
    }
}
