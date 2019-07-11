<?php

namespace Tests\Feature\User\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group users
 */
class RestoreWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_wrestler()
    {
        $this->actAs('basic-user');
        $wrestler = factory(Wrestler::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->put(route('wrestlers.restore', $wrestler));

        $response->assertForbidden();
    }
}
