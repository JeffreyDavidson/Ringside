<?php

namespace Tests\Feature\Generic\Wrestlers;

use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generic
 */
class RecoverWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_wrestler_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->put(route('wrestlers.recover', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduced_wrestler_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('pending-introduced')->create();

        $response = $this->put(route('wrestlers.recover', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_wrestler_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->put(route('wrestlers.recover', $wrestler));

        $response->assertForbidden();
    }

    /** @test */
    public function an_retired_wrestler_cannot_be_recovered()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->put(route('wrestlers.recover', $wrestler));

        $response->assertForbidden();
    }
}
