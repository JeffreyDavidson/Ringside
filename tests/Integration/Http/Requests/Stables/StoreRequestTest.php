<?php

namespace Tests\Integration\Http\Requests\Stables;

use App\Http\Requests\Stables\StoreRequest;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\User;
use App\Models\Wrestler;
use Tests\Factories\StableRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group stables
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    use ValidatesRequests;

    /**
     * @test
     */
    public function an_administrator_is_authorized_to_make_this_request()
    {
        $administrator = User::factory()->administrator()->create();

        $this->createRequest(StoreRequest::class)
            ->by($administrator)
            ->assertAuthorized();
    }

    /**
     * @test
     */
    public function a_non_administrator_is_not_authorized_to_make_this_request()
    {
        $user = User::factory()->create();

        $this->createRequest(StoreRequest::class)
            ->by($user)
            ->assertNotAuthorized();
    }

    /**
     * @test
     */
    public function stable_name_is_required()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'name' => null,
            ]))
            ->assertFailsValidation(['name' => 'required']);
    }

    /**
     * @test
     */
    public function stable_name_must_be_a_string()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'name' => 123,
            ]))
            ->assertFailsValidation(['name' => 'string']);
    }

    /**
     * @test
     */
    public function stable_name_must_be_at_least_3_characters()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'name' => 'ab',
            ]))
            ->assertFailsValidation(['name' => 'min:3']);
    }

    /**
     * @test
     */
    public function stable_name_must_be_unique()
    {
        Stable::factory()->create(['name' => 'Example Stable Name']);

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'name' => 'Example Stable Name',
            ]))
            ->assertFailsValidation(['name' => 'unique:stables,name,NULL,id']);
    }

    /**
     * @test
     */
    public function stable_started_at_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'started_at' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function stable_started_at_must_be_a_string_if_provided()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'started_at' => 12345,
            ]))
            ->assertFailsValidation(['started_at' => 'string']);
    }

    /**
     * @test
     */
    public function stable_started_at_must_be_in_the_correct_date_format()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'started_at' => 'not-a-date',
            ]))
            ->assertFailsValidation(['started_at' => 'date']);
    }

    /**
     * @test
     */
    public function stable_wrestlers_must_be_an_array()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => 'not-an-array',
            ]))
            ->assertFailsValidation(['wrestlers' => 'array']);
    }

    /**
     * @test
     */
    public function stable_tag_teams_must_be_an_array()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'tag_teams' => 'not-an-array',
            ]))
            ->assertFailsValidation(['tag_teams' => 'array']);
    }

    /**
     * @test
     */
    public function each_wrestler_in_a_stable_must_be_an_integer()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => ['not-an-integer'],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'integer']);
    }

    /**
     * @test
     */
    public function each_wrestler_in_a_stable_must_be_distinct()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [1, 1],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'distinct']);
    }

    /**
     * @test
     */
    public function each_wrestler_in_a_stable_must_exist()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [1, 2],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'exists']);
    }

    /**
     * @test
     */
    public function each_wrestler_must_be_able_to_join_the_stable()
    {
        $stable = Stable::factory()
            ->hasAttached(Wrestler::factory(), ['joined_at' => now()->toDateTimeString()])
            ->create();
        $wrestlerToJoinStable = $stable->currentWrestlers->first();

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [$wrestlerToJoinStable->id, 2],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'app\rules\wrestlercanjoinstable']);
    }

    /**
     * @test
     */
    public function each_tag_team_in_a_stable_must_be_an_integer()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'tag_teams' => ['not-an-integer'],
            ]))
            ->assertFailsValidation(['tag_teams.0' => 'integer']);
    }

    /**
     * @test
     */
    public function each_tag_teams_in_a_stable_must_be_distinct()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'tag_teams' => [1, 1],
            ]))
            ->assertFailsValidation(['tag_teams.0' => 'distinct']);
    }

    /**
     * @test
     */
    public function each_tag_teams_in_a_stable_must_exist()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'tag_teams' => [1, 2],
            ]))
            ->assertFailsValidation(['tag_teams.0' => 'exists']);
    }

    /**
     * @test
     */
    public function each_tag_teams_must_be_able_to_join_the_stable()
    {
        $stable = Stable::factory()
            ->hasAttached(TagTeam::factory(), ['joined_at' => now()->toDateTimeString()])
            ->create();
        $tagTeamToJoinStable = $stable->currentTagTeams->first();

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'tag_teams' => [$tagTeamToJoinStable->id, 2],
            ]))
            ->assertFailsValidation(['tag_teams.0' => 'app\rules\tagteamcanjoinstable']);
    }

    /**
     * @test
     */
    public function stable_must_have_a_minimum_number_of_3_members()
    {
        $wrestlerA = Wrestler::factory()->create();
        $wrestlerB = Wrestler::factory()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [$wrestlerA->id, $wrestlerB->id],
                'tag_teams' => [],
            ]))
            ->assertFailsValidation(['tag_teams' => 'app\rules\stablehasenoughmembers'])
            ->assertFailsValidation(['tag_teams' => 'app\rules\stablehasenoughmembers']);
    }

    /**
     * @test
     */
    public function stable_can_have_one_wrestler_and_one_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();
        $wrestler = Wrestler::factory()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [$wrestler->id],
                'tag_teams' => [$tagTeam->id],
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function a_stable_cannot_be_formed_with_only_one_tag_team_and_no_wrestlers()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [],
                'tag_teams' => [$tagTeam->id],
            ]))
            ->assertFailsValidation(['wrestlers' => 'app\rules\stablehasenoughmembers'])
            ->assertFailsValidation(['tag_teams' => 'app\rules\stablehasenoughmembers']);
    }

    /**
     * @test
     */
    public function a_stable_can_contain_at_least_two_tag_teams_with_no_wrestlers()
    {
        $tagTeamA = TagTeam::factory()->create();
        $tagTeamB = TagTeam::factory()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [],
                'tag_teams' => [$tagTeamA->id, $tagTeamB->id],
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function a_stable_can_contain_at_least_three_wrestlers_with_no_tag_teams()
    {
        $wrestlerA = Wrestler::factory()->create();
        $wrestlerB = Wrestler::factory()->create();
        $wrestlerC = Wrestler::factory()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [$wrestlerA->id, $wrestlerB->id, $wrestlerC->id],
                'tag_teams' => [],
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function a_stable_cannot_contain_a_wrestler_that_is_added_in_a_tag_team()
    {
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()
            ->hasAttached($wrestler, ['joined_at' => now()->toDateTimeString()])
            ->hasAttached(Wrestler::factory(), ['joined_at' => now()->toDateTimeString()])
            ->create();

        $this->createRequest(StoreRequest::class)
            ->validate(StableRequestDataFactory::new()->create([
                'wrestlers' => [$wrestler->id],
                'tag_teams' => [$tagTeam->id],
            ]))
            ->assertFailsValidation(['wrestlers' => 'app\rules\wrestlerjoinedstableintagteam']);
    }
}
