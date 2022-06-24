<?php

use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\MatchType;
use App\Models\Title;
use App\Models\Wrestler;
use Database\Seeders\MatchTypesTableSeeder;
use Tests\RequestFactories\EventMatchRequestFactory;

beforeEach(fn () => $this->seed(MatchTypesTableSeeder::class));

test('an administrator is authorized to make this request', function () {
    $this->createRequest(StoreRequest::class)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $this->createRequest(StoreRequest::class)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('event match type id is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'match_type_id' => null,
        ]))
        ->assertFailsValidation(['match_type_id' => 'required']);
});

test('event match type id must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'match_type_id' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['match_type_id' => 'integer']);
});

test('event match type must exist', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'match_type_id' => 999999,
        ]))
        ->assertFailsValidation(['match_type_id' => 'exists']);
});

test('event match referees is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'referees' => null,
        ]))
        ->assertFailsValidation(['referees' => 'required']);
});

test('event match referees must be an array', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'referees' => 'not-an-array',
        ]))
        ->assertFailsValidation(['referees' => 'array']);
});

test('each event match referees must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'referees' => ['not-an-integer'],
        ]))
        ->assertFailsValidation(['referees.0' => 'integer']);
});

test('each event match referees must be distinct', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'referees' => [1, 1],
        ]))
        ->assertFailsValidation(['referees.0' => 'distinct']);
});

test('each event match referees must exist', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'referees' => [999999],
        ]))
        ->assertFailsValidation(['referees.0' => 'exists']);
});

test('event match titles is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => [],
        ]))
        ->assertPassesValidation();
});

test('event match titles must be an array', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => 'not-an-array',
        ]))
        ->assertFailsValidation(['titles' => 'array']);
});

test('each event match titles must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => ['not-an-integer'],
        ]))
        ->assertFailsValidation(['titles.0' => 'integer']);
});

test('each event match titles must be a distinct', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => [1, 1],
        ]))
        ->assertFailsValidation(['titles.0' => 'distinct']);
});

test('each event match titles must be exist', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => [999999],
        ]))
        ->assertFailsValidation(['titles.0' => 'exists']);
});

test('each event match titles must be active', function () {
    $title = Title::factory()->nonActive()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => [$title->id],
        ]))
        ->assertFailsValidation(['titles.0' => 'app\rules\titlemustbeactive']);
});

test('each event match competitors is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'competitors' => null,
        ]))
        ->assertFailsValidation(['competitors' => 'required']);
});

test('each event match competitors must be an array', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'competitors' => 'not-an-array',
        ]))
        ->assertFailsValidation(['competitors' => 'array']);
});

test('each event match competitors array must contain at least two items', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'competitors' => [
                [],
            ],
        ]))
        ->assertFailsValidation(['competitors' => 'min:2']);
});

test('each event match competitors items in the array must equal number of sides of the match type', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'match_type_id' => MatchType::factory()->create(['number_of_sides' => 2])->id,
            'competitors' => [
                [['competitor_id' => 1, 'competitor_type' => 'wrestler']],
                [['competitor_id' => 2, 'competitor_type' => 'wrestler']],
                [['competitor_id' => 3, 'competitor_type' => 'wrestler']],
            ],
        ]))
        ->assertFailsValidation([
            'competitors' => 'app\rules\competitorsgroupedintocorrectnumberofsidesformatchtype',
        ]);
});

test('each_event_match_preview_is_optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'preview' => null,
        ]))
        ->assertPassesValidation();
});

test('title with champion must be included in competitors for title match', function () {
    $champion = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->withChampion($champion)->create();
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();

    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => [$title->id],
            'competitors' => [
                [['competitor_id' => $wrestlerA->id, 'competitor_type' => 'wrestler']],
                [['competitor_id' => $wrestlerB->id, 'competitor_type' => 'wrestler']],
            ],
        ]))
        ->assertFailsValidation([
            'competitors' => 'app\rules\titlechampionincludedintitlematch',
        ]);
});

test('title with champion must be included in competitors for title match two', function () {
    $champion = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->withChampion($champion)->create();
    $nonChampionWrestler = Wrestler::factory()->bookable()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => [$title->id],
            'competitors' => [
                [['competitor_id' => $champion->id, 'competitor_type' => 'wrestler']],
                [['competitor_id' => $nonChampionWrestler->id, 'competitor_type' => 'wrestler']],
            ],
        ]))
        ->assertPassesValidation();
});
