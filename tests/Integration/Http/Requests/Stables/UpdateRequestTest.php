<?php

namespace Tests\Integration\Http\Requests\Stables;

use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Activation;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Tests\Factories\StableRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group stables
 * @group roster
 * @group requests
 */
class UpdateRequestTest extends TestCase
{
    use ValidatesRequests;

    /**
     * @test
     */
    public function stable_name_is_required()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'name' => null,
            ]))
            ->assertFailsValidation(['name' => 'required']);
    }

    /**
     * @test
     */
    public function stable_name_must_be_a_string()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'name' => 123,
            ]))
            ->assertFailsValidation(['name' => 'string']);
    }

    /**
     * @test
     */
    public function stable_name_must_be_at_least_3_characters()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'name' => 'ab',
            ]))
            ->assertFailsValidation(['name' => 'min:3']);
    }

    /**
     * @test
     */
    public function stable_name_must_be_unique()
    {
        $stableA = Stable::factory()->create();
        Stable::factory()->create(['name' => 'Example Stable']);

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stableA)
            ->validate(StableRequestDataFactory::new()->withStable($stableA)->create([
                'name' => 'Example Stable',
            ]))
            ->assertFailsValidation(['name' => 'unique:stables,NULL,1,id']);
    }

    /**
     * @test
     */
    public function stable_started_at_is_optional_if_not_activated()
    {
        $stable = Stable::factory()->unactivated()->withNoMembers()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'started_at' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function stable_started_at_is_required_if_active()
    {
        $stable = Stable::factory()->active()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'started_at' => null,
            ]))
            ->assertFailsValidation(['started_at' => 'required']);
    }

    /**
     * @test
     */
    public function stable_started_at_must_be_a_string_if_provided()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'started_at' => 12345,
            ]))
            ->assertFailsValidation(['started_at' => 'string']);
    }

    /**
     * @test
     */
    public function stable_started_at_must_be_in_the_correct_date_format()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'started_at' => 'not-a-date-format',
            ]))
            ->assertFailsValidation(['started_at' => 'date']);
    }

    /**
     * @test
     */
    public function stable_started_at_cannot_be_changed_if_stable_date_has_past()
    {
        $stable = Stable::factory()->active()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'started_at' => Carbon::now()->toDateTimeString(),
            ]))
            ->assertFailsValidation(['activated_at' => 'activation_date_cannot_be_changed']);
    }

    /**
     * @test
     */
    public function stable_started_at_can_be_changed_if_activation_start_date_is_in_the_future()
    {
        $stable = Stable::factory()->withFutureActivation()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'started_at' => Carbon::tomorrow()->toDateString(),
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function stable_wrestlers_must_be_an_array()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'wrestlers' => 'not-an-array',
            ]))
            ->assertFailsValidation(['wrestlers' => 'array']);
    }

    /**
     * @test
     */
    public function stable_tag_teams_must_be_an_array()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'tag_teams' => 'not-an-array',
            ]))
            ->assertFailsValidation(['tag_teams' => 'array']);
    }

    /**
     * @test
     */
    public function each_wrestler_in_a_stable_must_be_an_integer()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'wrestlers' => ['not-an-integer'],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'integer']);
    }

    /**
     * @test
     */
    public function each_wrestler_in_a_stable_must_be_distinct()
    {
        $stable = Stable::factory()
            ->hasAttached(Wrestler::factory()->count(2), ['joined_at' => now()->toDateTimeString()])
            ->create();
        $currentWrestlers = $stable->currentWrestlers;

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'wrestlers' => [$currentWrestlers->first()->id, $currentWrestlers->first()->id],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'distinct']);
    }

    /**
     * @test
     */
    public function each_wrestler_in_a_stable_must_exist()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'wrestlers' => [1, 2],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'exists']);
    }

    /**
     * @test
     */
    public function a_suspended_wrestler_cannot_join_the_stable()
    {
        $stable = Stable::factory()->withEmployedDefaultMembers()->create();
        $wrestlerNotInStable = Wrestler::factory()->suspended()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'wrestlers' => [$stable->currentWrestlers->first()->getKey(), $wrestlerNotInStable->getKey()],
                'tag_teams' => $stable->currentTagTeams->modelKeys(),
            ]))
            ->assertFailsValidation(['wrestlers.1' => 'cannot_join_stable']);
    }

    /**
     * @test
     */
    public function an_injured_wrestler_cannot_join_the_stable()
    {
        $stable = Stable::factory()->withEmployedDefaultMembers()->create();
        $wrestlerNotInStable = Wrestler::factory()->injured()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'wrestlers' => [$stable->currentWrestlers->first()->getKey(), $wrestlerNotInStable->getKey()],
                'tag_teams' => $stable->currentTagTeams->modelKeys(),
            ]))
            ->assertFailsValidation(['wrestlers.1' => 'cannot_join_stable']);
    }

    /**
     * @test
     */
    public function each_tag_team_in_a_stable_must_be_an_integer()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'tag_teams' => ['not-an-integer'],
            ]))
            ->assertFailsValidation(['tag_teams.0' => 'integer']);
    }

    /**
     * @test
     */
    public function each_tag_teams_in_a_stable_must_be_distinct()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'tag_teams' => [1, 1],
            ]))
            ->assertFailsValidation(['tag_teams.0' => 'distinct']);
    }

    /**
     * @test
     */
    public function each_tag_teams_in_a_stable_must_exist()
    {
        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'tag_teams' => [1, 2],
            ]))
            ->assertFailsValidation(['tag_teams.0' => 'exists']);
    }

    /**
     * @test
     */
    public function suspended_tag_teams_cannot_join_a_stable()
    {
        $stable = Stable::factory()
            ->hasAttached(TagTeam::factory(), ['joined_at' => now()->toDateTimeString()])
            ->create();
        $tagTeamToJoinStable = $stable->currentTagTeams->first();
        $tagTeamNotInStable = TagTeam::factory()->suspended()->create();

        $stable = Stable::factory()->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'tag_teams' => [$tagTeamToJoinStable->getKey(), $tagTeamNotInStable->getKey()],
            ]))
            ->assertFailsValidation(['tag_teams.1' => 'cannot_join_stable']);
    }

    /**
     * @test
     */
    public function stable_must_have_a_minimum_number_of_members()
    {
        $stable = Stable::factory()->active()->withEmployedDefaultMembers()->create();
        $wrestlersToJoinStable = Wrestler::factory()->bookable()->count(2)->create();

        $this->createRequest(UpdateRequest::class)
            ->withParam('stable', $stable)
            ->validate(StableRequestDataFactory::new()->withStable($stable)->create([
                'wrestlers' => $wrestlersToJoinStable->modelKeys(),
                'tag_teams' => [],
            ]))
            ->assertFailsValidation(['*' => 'not_enough_members']);
    }
}
