<?php

namespace Tests\Feature\MatchDecisions;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddMatchDecisionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);
    }

    /** @test */
    public function a_pinfall_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Pinfall', 'slug' => 'pinfall']);
    }

    /** @test */
    public function a_submission_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Submission', 'slug' => 'submission']);
    }

    /** @test */
    public function a_disqualification_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Disqualification', 'slug' => 'dq']);
    }

    /** @test */
    public function a_countout_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Countout', 'slug' => 'countout']);
    }

    /** @test */
    public function a_knockout_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Knockout', 'slug' => 'knockout']);
    }

    /** @test */
    public function a_stipulation_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Stipulation', 'slug' => 'stipulation']);
    }

    /** @test */
    public function a_forfeit_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Forfeit', 'slug' => 'forfeit']);
    }

    /** @test */
    public function a_time_limit_draw_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Time Limit Draw', 'slug' => 'draw']);
    }

    /** @test */
    public function a_no_decision_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'No Decision', 'slug' => 'nodecision']);
    }

    /** @test */
    public function a_reverse_decision_match_decision_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_decisions', ['name' => 'Reverse Decision', 'slug' => 'revdecision']);
    }
}
