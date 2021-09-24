<?php

namespace Tests\Integration\Http\Requests\EventMatches;

use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\User;
use Database\Seeders\MatchTypesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase,
        ValidatesRequests;

    public function setUp(): void
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
    public function each_event_match_preview_is_optional()
    {
        $this->createRequest(StoreRequest::class)
            ->validate(EventMatchRequestDataFactory::new()->create([
                'preview' => null,
            ]))
            ->assertPassesValidation();
    }
}
