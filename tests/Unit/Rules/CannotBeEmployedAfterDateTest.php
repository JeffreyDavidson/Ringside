<?php

namespace Tests\Unit\Rules;

use App\Rules\CannotBeEmployedAfterDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EmploymentFactory;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group rules
 */
class CannotBeEmployedAfterDateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_unemployed_wrestler_cannot_join_a_tag_team()
    {
        $wrestler = WrestlerFactory::new()->unemployed()->create();

        $this->assertFalse((new CannotBeEmployedAfterDate(now()->toDateTimeString()))->passes(null, $wrestler->id));
    }

    /** @test */
    public function a_wrestler_can_have_a_current_employment_date_before_the_tag_team_start_date()
    {
        $wrestler = WrestlerFactory::new()->employed(
            EmploymentFactory::new()->started(now()->subDays(2)->toDateTimeString())
        )->create();

        $this->assertTrue((new CannotBeEmployedAfterDate(now()->toDateTimeString()))->passes(null, $wrestler->id));
    }

    /** @test */
    public function a_wrestler_cannot_have_a_current_employment_date_after_the_tag_team_start_date()
    {
        $wrestler = WrestlerFactory::new()->employed(
            EmploymentFactory::new()->started(now()->subDays(2)->toDateTimeString())
        )->create();

        $this->assertFalse((new CannotBeEmployedAfterDate(now()->subDays(3)->toDateTimeString()))->passes(null, $wrestler->id));
    }

    /** @test */
    public function a_wrestler_can_have_a_future_employment_date_before_the_tag_team_start_date()
    {
        $wrestler = WrestlerFactory::new()
            ->employed(
                EmploymentFactory::new()->started(now()->addDay()->toDateTimeString())
            )
            ->create();

        $this->assertTrue((new CannotBeEmployedAfterDate(now()->addWeek()->toDateTimeString()))->passes(null, $wrestler->id));
    }

    /** @test */
    public function a_wrestler_cannot_have_a_future_employment_date_after_the_tag_team_start_date()
    {
        $wrestler = WrestlerFactory::new()
            ->employed(
                EmploymentFactory::new()->started(now()->addDays(4)->toDateTimeString())
            )
            ->create();

        $this->assertFalse((new CannotBeEmployedAfterDate(now()->addDays(3)->toDateTimeString()))->passes(null, $wrestler->id));
    }
}
