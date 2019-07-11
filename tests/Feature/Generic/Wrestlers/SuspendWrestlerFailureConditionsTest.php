<?php

namespace Tests\Feature\Generic\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generic
 */
class SuspendWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_already_suspendeded_wrestler_cannot_be_suspended()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->put(route('wrestlers.suspend', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_wrestler_cannot_be_suspended()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->put(route('wrestlers.suspend', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function an_inactive_wrestler_cannot_be_suspended()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->put(route('wrestlers.suspend', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_suspended()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->put(route('wrestlers.suspend', $wrestler));

        $response->assertForbidden();
    }
}
