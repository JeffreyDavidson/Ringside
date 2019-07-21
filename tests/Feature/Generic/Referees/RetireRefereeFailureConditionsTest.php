<?php

namespace Tests\Feature\Generic\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 */
class RetireRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_already_retired_referee_cannot_be_retired()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->put(route('referees.retire', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduced_retired_referee_cannot_be_retired()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->put(route('referees.retire', $referee));

        $response->assertForbidden();
    }
}
