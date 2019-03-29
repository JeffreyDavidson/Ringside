<?php

namespace Tests\Feature\MatchTypes;

use Tests\TestCase;
use App\Models\MatchType;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddMatchTypesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);
    }

    /** @test */
    public function a_singles_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Singles', 'slug' => 'singles']);
    }

    /** @test */
    public function a_tag_team_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Tag Team', 'slug' => 'tagteam']);
    }

    /** @test */
    public function a_triangle_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Triangle', 'slug' => 'triangle']);
    }

    /** @test */
    public function a_triple_threat_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Triple Threat', 'slug' => 'triple']);
    }

    /** @test */
    public function a_fatal_four_way_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Fatal 4 Way', 'slug' => 'fatal4way']);
    }

    /** @test */
    public function a_six_man_tag_team_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => '6 Man Tag Team', 'slug' => '6man']);
    }

    /** @test */
    public function a_eight_man_tag_team_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => '8 Man Tag Team', 'slug' => '8man']);
    }

    /** @test */
    public function a_ten_man_tag_team_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => '10 Man Tag Team', 'slug' => '10man']);
    }

    /** @test */
    public function a_two_on_one_handicap_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Two On One Handicap', 'slug' => '21handicap']);
    }

    /** @test */
    public function a_three_on_two_handicap_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Three On Two Handicap', 'slug' => '32handicap']);
    }

    /** @test */
    public function a_battle_royal_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Battle Royal', 'slug' => 'battleroyal']);
    }

    /** @test */
    public function a_royal_rumble_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Royal Rumble', 'slug' => 'royalrumble']);
    }

    /** @test */
    public function a_tornado_tag_team_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Tornado Tag Team', 'slug' => 'tornadotag']);
    }

    /** @test */
    public function a_gauntlet_match_type_is_saved_in_database()
    {
        $this->assertDatabaseHas('match_types', ['name' => 'Gauntlet', 'slug' => 'gauntlet']);
    }
}
