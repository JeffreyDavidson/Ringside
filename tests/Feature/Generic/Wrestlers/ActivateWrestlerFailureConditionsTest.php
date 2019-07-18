<?php

namespace Tests\Feature\Generic\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generics
 */
class ActivateWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_wrestler_cannot_be_activated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->put(route('wrestlers.activate', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_wrestler_cannot_be_activated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->put(route('wrestlers.activate', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_wrestler_cannot_be_activated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->put(route('wrestlers.activate', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_activated()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->put(route('wrestlers.activate', $wrestler));

        $response->assertForbidden();
    }
}
