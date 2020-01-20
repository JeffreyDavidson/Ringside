<?php

namespace Tests\Feature\Generic\Referees;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Referee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group referees
 * @group generics
 * @group roster
 */
class ClearInjuryRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_bookable_referee_cannot_be_cleared_from_an_injury()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('bookable')->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_referee_cannot_be_cleared_from_an_injury()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('pending-employment')->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_referee_cannot_be_cleared_from_an_injury()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('retired')->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_referee_cannot_be_cleared_from_an_injury()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $this->actAs('administrator');
        $referee = factory(Referee::class)->states('suspended')->create();

        $response = $this->clearInjuryRequest($referee);

        $response->assertForbidden();
    }
}