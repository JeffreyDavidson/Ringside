<?php

namespace Tests\Integration\Http\Requests\TagTeams;

use App\Http\Requests\TagTeams\StoreRequest;
use App\Models\Employment;
use App\Models\Suspension;
use App\Models\TagTeam;
use App\Models\User;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Tests\Factories\TagTeamRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group tagteams
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
    public function tag_team_name_is_required()
    {
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
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
        TagTeam::factory()->create(['name' => 'Example TagTeam Name']);

        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'name' => 'Example TagTeam Name',
            ]))
            ->assertFailsValidation(['name' => Rule::unique('tag_teams', 'name')]);
    }

    /**
     * @test
     */
    public function tag_team_signature_move_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'signature_move' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function tag_team_signature_move_must_be_a_string_if_provided()
    {
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
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
        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'started_at' => 'not-a-date',
            ]))
            ->assertFailsValidation(['started_at' => 'date']);
    }

    /**
     * @test
     */
    public function tag_team_wrestlers_are_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function tag_team_wrestlers_must_be_an_array_if_provided()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => 'not-an-array',
            ]))
            ->assertFailsValidation(['wrestlers' => 'array']);
    }

    /**
     * @test
     */
    public function tag_team_wrestlers_is_required_with_a_tag_team_signature_move()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => null,
                'signature_move' => 'Example Signature Mo)ve',
            ]))
            ->assertFailsValidation(['wrestlers' => 'requiredwith:signature_move']);
    }

    /**
     * @test
     */
    public function each_tag_team_wrestler_must_be_an_integer()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => ['not-an-integer'],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'integer']);
    }

    /**
     * @test
     */
    public function each_tag_team_wrestler_must_be_distinct()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => [1, 1],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'distinct']);
    }

    /**
     * @test
     */
    public function each_tag_team_wrestler_must_exist()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => [1, 2],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'exist']);
    }

    /**
     * @test
     */
    public function each_tag_team_wrestler_cannot_be_suspended_to_join_a_tag_team()
    {
        $wrestlerA = Wrestler::factory()->suspended()->create();
        $wrestlerB = Wrestler::factory()->bookable()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => [$wrestlerA->id, $wrestlerB->id],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'cannot_be_suspended_to_join_tag_team']);
    }

    /**
     * @test
     */
    public function each_tag_team_wrestler_cannot_be_injured_to_join_a_tag_team()
    {
        $wrestlerA = Wrestler::factory()->injured()->create();
        $wrestlerB = Wrestler::factory()->bookable()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => [$wrestlerA->id, $wrestlerB->id],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'cannot_be_injured_to_join_tag_team']);
    }

    /**
     * @test
     */
    public function each_tag_team_wrestler_cannot_join_multiple_bookable_tag_team()
    {
        $tagTeam = TagTeam::factory()
            ->bookable()
            ->has(Wrestler::factory()->bookable()->count(2))
            ->bookable()
            ->create();

        $wrestlerB = Wrestler::factory()
            ->bookable()
            ->create();

        $this->createRequest(StoreRequest::class)
            ->validate(TagTeamRequestDataFactory::new()->create([
                'wrestlers' => [$tagTeam->currentWrestlers->first()->getKey(), $wrestlerB->getKey()],
            ]))
            ->assertFailsValidation(['wrestlers.0' => 'cannot_belong_to_multiple_employed_tag_teams']);
    }
}
