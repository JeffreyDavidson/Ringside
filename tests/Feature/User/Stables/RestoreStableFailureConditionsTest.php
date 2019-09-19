<?php

namespace Tests\Feature\User\Stables;

use Tests\TestCase;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group users
 * @group roster
 */
class RestoreStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->create();
        $stable->delete();

        $response = $this->put(route('stables.restore', $stable));

        $response->assertForbidden();
    }
}
