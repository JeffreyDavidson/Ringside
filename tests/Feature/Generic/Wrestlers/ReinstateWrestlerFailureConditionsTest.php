<?php

namespace Tests\Feature\Generic\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generic
 */
class ReinstateWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_wrestler_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->put(route('wrestlers.reinstate', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduced_wrestler_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('pending-introduced')->create();

        $response = $this->put(route('wrestlers.reinstate', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->put(route('wrestlers.reinstate', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function an_retired_wrestler_cannot_be_reinstated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->put(route('wrestlers.reinstate', $wrestler));

        $response->assertForbidden();
    }
}
