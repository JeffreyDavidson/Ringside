<?php

namespace Tests\Integration\Http\Requests\EventMatches;

use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\MatchType;
use App\Models\Title;
use App\Models\TitleChampionship;
use App\Models\User;
use App\Models\Wrestler;
use Database\Seeders\MatchTypesTableSeeder;
use Tests\Factories\EventMatchRequestDataFactory;
use Tests\TestCase;
use Tests\ValidatesRequests;

/**
 * @group events
 * @group roster
 * @group requests
 */
class StoreRequestTest extends TestCase
{
    use ValidatesRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MatchTypesTableSeeder::class);
    }

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
    public function event_match_type_id_is_required()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'match_type_id' => null,
            ]))
            ->assertFailsValidation(['match_type_id' => 'required']);
    }

    /**
     * @test
     */
    public function event_match_type_id_must_be_an_integer()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'match_type_id' => 'not-an-integer',
            ]))
            ->assertFailsValidation(['match_type_id' => 'integer']);
    }

    /**
     * @test
     */
    public function event_match_type_must_exist()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'match_type_id' => 999999,
            ]))
            ->assertFailsValidation(['match_type_id' => 'exists']);
    }

    /**
     * @test
     */
    public function event_match_referees_is_required()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'referees' => null,
            ]))
            ->assertFailsValidation(['referees' => 'required']);
    }

    /**
     * @test
     */
    public function event_match_referees_must_be_an_array()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'referees' => 'not-an-array',
            ]))
            ->assertFailsValidation(['referees' => 'array']);
    }

    /**
     * @test
     */
    public function each_event_match_referees_must_be_an_integer()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'referees' => ['not-an-integer'],
            ]))
            ->assertFailsValidation(['referees.0' => 'integer']);
    }

    /**
     * @test
     */
    public function each_event_match_referees_must_be_distinct()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'referees' => [1, 1],
            ]))
            ->assertFailsValidation(['referees.0' => 'distinct']);
    }

    /**
     * @test
     */
    public function each_event_match_referees_must_exist()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'referees' => [999999],
            ]))
            ->assertFailsValidation(['referees.0' => 'exists']);
    }

    /**
     * @test
     */
    public function event_match_titles_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'titles' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function event_match_titles_must_be_an_array()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'titles' => 'not-an-array',
            ]))
            ->assertFailsValidation(['titles' => 'array']);
    }

    /**
     * @test
     */
    public function each_event_match_titles_must_be_an_integer()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'titles' => ['not-an-integer'],
            ]))
            ->assertFailsValidation(['titles.0' => 'integer']);
    }

    /**
     * @test
     */
    public function each_event_match_titles_must_be_distinct()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'titles' => [1, 1],
            ]))
            ->assertFailsValidation(['titles.0' => 'distinct']);
    }

    /**
     * @test
     */
    public function each_event_match_titles_must_exist()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'titles' => [999999],
            ]))
            ->assertFailsValidation(['titles.0' => 'exists']);
    }

    /**
     * @test
     */
    public function each_event_match_titles_must_be_active()
    {
        $title = Title::factory()->nonActive()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'titles' => [$title->id],
            ]))
            ->assertFailsValidation(['titles' => 'app\rules\titlesmustbeactive']);
    }

    /**
     * @test
     */
    public function each_event_match_competitors_is_required()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'competitors' => null,
            ]))
            ->assertFailsValidation(['competitors' => 'required']);
    }

    /**
     * @test
     */
    public function each_event_match_competitors_must_be_an_array()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'competitors' => 'not-an-array',
            ]))
            ->assertFailsValidation(['competitors' => 'array']);
    }

    /**
     * @test
     */
    public function each_event_match_competitors_array_must_contain_at_least_two_items()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'competitors' => [
                    [],
                ],
            ]))
            ->assertFailsValidation(['competitors' => 'min:2']);
    }

    /**
     * @test
     */
    public function each_event_match_competitors_items_in_the_array_must_equal_number_of_sides_of_the_match_type()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'match_type_id' => MatchType::factory()->create(['number_of_sides' => 2])->id,
                'competitors' => [
                    [['competitor_id' => 1, 'competitor_type' => 'wrestler']],
                    [['competitor_id' => 2, 'competitor_type' => 'wrestler']],
                    [['competitor_id' => 3, 'competitor_type' => 'wrestler']],
                ],
            ]))->assertFailsValidation([
                'competitors' => 'app\rules\competitorsgroupedintocorrectnumberofsidesformatchtype',
            ]);
    }

    /**
     * @test
     */
    public function each_event_match_preview_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'preview' => null,
            ]))
            ->assertPassesValidation();
    }

    /**
     * @test
     */
    public function title_with_champion_must_be_included_in_competitors_for_title_match()
    {
        $champion = Wrestler::factory()->bookable()->create();
        $title = Title::factory()->active()->withChampion($champion)->create();
        [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();

        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'titles' => [$title->id],
                'competitors' => [
                    [['competitor_id' => $wrestlerA->id, 'competitor_type' => 'wrestler']],
                    [['competitor_id' => $wrestlerB->id, 'competitor_type' => 'wrestler']],
                ],
            ]))
            ->assertFailsValidation([
                'competitors' => 'app\rules\titlechampionincludedintitlematch',
            ]);
    }

    /**
     * @test
     */
    public function title_with_champion_must_be_included_in_competitors_for_title_match_two()
    {
        $champion = Wrestler::factory()->bookable()->create();
        $title = Title::factory()->active()->withChampion($champion)->create();
        $nonChampionWrestler = Wrestler::factory()->bookable()->create();

        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'titles' => [$title->id],
                'competitors' => [
                    [['competitor_id' => $champion->id, 'competitor_type' => 'wrestler']],
                    [['competitor_id' => $nonChampionWrestler->id, 'competitor_type' => 'wrestler']],
                ],
            ]))
            ->assertPassesValidation();
    }
}
