<?php

namespace Tests\Feature\User\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 */
class RestoreRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();
        $referee->delete();

        $response = $this->put(route('referees.restore', $referee));

        $response->assertForbidden();
    }
}
