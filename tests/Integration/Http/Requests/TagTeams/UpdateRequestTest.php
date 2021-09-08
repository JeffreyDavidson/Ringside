<?php

namespace Tests\Integration\Http\Requests\TagTeams;

use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\Employment;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group tagteams
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase,
        ValidatesRequests;

    /**
     * @test
     */
    public function tag_team_name_is_required()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'name' => null,
            ]))
            ->assertFailsValidation(['name' => 'required']);
    }

    /**
     * @test
     */
    public function tag_team_name_must_be_a_string()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'name' => 123,
            ]))
            ->assertFailsValidation(['name' => 'string']);
    }

    /**
     * @test
     */
    public function tag_team_name_must_be_at_least_3_characters()
    {
        $tagTeam = TagTeam::factory()->make();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'name' => 'ab',
            ]))
            ->assertFailsValidation(['name' => 'min:3']);
    }

    /**
     * @test
     */
    public function tag_team_name_must_be_unique()
    {
        $tagTeamA = TagTeam::factory()->create(['name' => 'Example Tag Team Name A']);
        $tagTeamB = TagTeam::factory()->create(['name' => 'Example Tag Team Name B']);

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeamA)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'name' => 'Example Tag Team Name B',
            ]))
            ->assertFailsValidation(['name' => 'unique:tag_teams,NULL,1,id']);
    }

    /**
     * @test
     */
    public function tag_team_signature_move_is_optional_without_wrestlers_filled()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'signature_move' => null,
                'wrestlers' => [],
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function tag_team_signature_move_must_be_a_string_if_provided()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'signature_move' => 12345,
            ]))
            ->assertFailsValidation(['signature_move' => 'string']);
    }

    /**
     * @test
     */
    public function tag_team_started_at_is_optional()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'started_at' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function tag_team_started_at_must_be_a_string_if_provided()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'started_at' => 12345,
            ]))
            ->assertFailsValidation(['started_at' => 'string']);
    }

    /**
     * @test
     */
    public function tag_team_started_at_must_be_in_the_correct_date_format()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'started_at' => 'not-a-date-format',
            ]))
            ->assertFailsValidation(['started_at' => 'date']);
    }

    /**
     * @test
     */
    public function tag_team_started_at_cannot_be_changed_if_employment_start_date_has_past()
    {
        $tagTeam = TagTeam::factory()->has(Employment::factory()->started('2021-01-01'))->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'started_at' => '2021-01-01',
            ]))
            ->assertFailsValidation(['started_at' => 'app\rules\employmentstartdatecanbechanged']);
    }

    /**
     * @test
     */
    public function tag_team_started_at_can_be_changed_if_employment_start_date_is_in_the_future()
    {
        $tagTeam = TagTeam::factory()->has(Employment::factory()->started(Carbon::parse('+2 weeks')))->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('tag_team', $tagTeam)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'started_at' => Carbon::tomorrow()->toDateString(),
            ]))
            ->assertPassesValidation();
    }
}
