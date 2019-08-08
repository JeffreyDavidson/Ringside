<?php

namespace Tests\Feature\Generic\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generics
 */
class RestoreWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->put(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }

    /** @test */
    public function a_pending_introduction_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('pending-introduction')->create();

        $response = $this->put(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->put(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }

    /** @test */
    public function a_suspended_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->put(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->put(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }
}
