<?php

namespace Tests\Feature\Generic\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }

    /** @test */
    public function an_inactive_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }

    /** @test */
    public function a_suspended_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_restored()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->patch(route('wrestlers.restore', $wrestler));

        $response->assertNotFound();
    }
}
