<?php

namespace Tests\Unit\Rules;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class EmploymentStartDateCanBeChangedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_unemployed_wrestlers_start_date_can_be_changed()
    {
        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->assertTrue((new EmploymentStartDateCanBeChanged($wrestler))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_future_employed_wrestlers_start_date_can_be_changed()
    {
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this->assertTrue((new EmploymentStartDateCanBeChanged($wrestler))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_bookable_wrestlers_start_date_cannot_be_changed()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this->assertFalse((new EmploymentStartDateCanBeChanged($wrestler))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function an_unemployed_managers_start_date_can_be_changed()
    {
        $manager = Manager::factory()->unemployed()->create();

        $this->assertTrue((new EmploymentStartDateCanBeChanged($manager))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_future_employed_managers_start_date_can_be_changed()
    {
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->assertTrue((new EmploymentStartDateCanBeChanged($manager))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function an_available_managers_start_date_cannot_be_changed()
    {
        $manager = Manager::factory()->available()->create();

        $this->assertFalse((new EmploymentStartDateCanBeChanged($manager))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function an_unemployed_referees_start_date_can_be_changed()
    {
        $referee = Referee::factory()->unemployed()->create();

        $this->assertTrue((new EmploymentStartDateCanBeChanged($referee))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_future_employed_referees_start_date_can_be_changed()
    {
        $referee = Referee::factory()->withFutureEmployment()->create();

        $this->assertTrue((new EmploymentStartDateCanBeChanged($referee))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_bookable_referees_start_date_cannot_be_changed()
    {
        $referee = Referee::factory()->bookable()->create();

        $this->assertFalse((new EmploymentStartDateCanBeChanged($referee))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function an_unemployed_tag_teams_start_date_can_be_changed()
    {
        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->assertTrue((new EmploymentStartDateCanBeChanged($tagTeam))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_future_employed_tag_teams_start_date_can_be_changed()
    {
        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();

        $this->assertTrue((new EmploymentStartDateCanBeChanged($tagTeam))->passes(null, now()->toDateTimeString()));
    }

    /** @test */
    public function a_bookable_tag_teams_start_date_cannot_be_changed()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->assertFalse((new EmploymentStartDateCanBeChanged($tagTeam))->passes(null, now()->toDateTimeString()));
    }
}
