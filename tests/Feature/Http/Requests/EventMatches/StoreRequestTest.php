<?php

use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\Title;
use App\Models\Wrestler;
use App\Rules\CompetitorsAreNotDuplicated;
use App\Rules\CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType;
use App\Rules\RefereeCanRefereeMatch;
use App\Rules\TitleChampionIncludedInTitleMatch;
use App\Rules\TitleMustBeActive;
use Database\Seeders\MatchTypesTableSeeder;
use function Pest\Laravel\mock;
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

test('each event match referees must be able to referee the match', function () {
    $referee = Referee::factory()->create();

    mock(RefereeCanRefereeMatch::class)
        ->shouldReceive('validate')
        ->with('referees.0', $referee->id, function ($closure) {
            $closure();
        });

    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'referees' => [$referee->id],
        ]))
        ->assertFailsValidation(['referees.0' => RefereeCanRefereeMatch::class]);
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

    mock(TitleMustBeActive::class)
        ->shouldReceive('validate')
        ->with('titles.0', $title->id, function ($closure) {
            $closure();
        });

    $this->createRequest(StoreRequest::class)
        ->validate(EventMatchRequestFactory::new()->create([
            'titles' => [$title->id],
        ]))
        ->assertFailsValidation(['titles.0' => TitleMustBeActive::class]);
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
    $data = EventMatchRequestFactory::new()->create();
    data_set($data, 'competitors', [0 => []]);

    $this->createRequest(StoreRequest::class)
        ->validate($data)
        ->assertFailsValidation(['competitors' => 'min:2']);
});

test('each event match competitors items in the array must equal number of sides of the match type', function () {
    $data = EventMatchRequestFactory::new()->create([
        'match_type_id' => MatchType::factory()->create(['number_of_sides' => 2])->id,
    ]);
    $competitors = [
        ['wrestlers' => [1]],
        ['wrestlers' => [2]],
        ['wrestlers' => [3]],
    ];
    data_set($data, 'competitors', $competitors);

    $this->createRequest(StoreRequest::class)
        ->validate($data)
        ->assertFailsValidation([
            'competitors' => CompetitorsGroupedIntoCorrectNumberOfSidesForMatchType::class,
        ]);
});

test('each event match competitors items must not be duplicated', function () {
    $data = EventMatchRequestFactory::new()->create();
    $competitors = [
        ['wrestlers' => [1]],
        ['wrestlers' => [1]],
    ];
    data_set($data, 'competitors', $competitors);

    $this->createRequest(StoreRequest::class)
        ->validate($data)
        ->assertFailsValidation([
            'competitors' => CompetitorsAreNotDuplicated::class,
        ]);
});

test('each event match preview is optional', function () {
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
    $data = EventMatchRequestFactory::new()->create([
        'titles' => [$title->id],
        'competitors' => [
            ['wrestlers' => [$wrestlerA->id]],
            ['wrestlers' => [$wrestlerB->id]],
        ],
    ]);

    $this->createRequest(StoreRequest::class)
        ->validate($data)
        ->assertFailsValidation([
            'competitors' => TitleChampionIncludedInTitleMatch::class,
        ]);
});

test('title with champion must be included in competitors for title match two', function () {
    $champion = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->withChampion($champion)->create();
    $nonChampionWrestler = Wrestler::factory()->bookable()->create();
    $data = EventMatchRequestFactory::new()->create([
        'titles' => [$title->id],
        'competitors' => [
            ['wrestlers' => [$champion->id]],
            ['wrestlers' => [$nonChampionWrestler->id]],
        ],
    ]);

    $this->createRequest(StoreRequest::class)
        ->validate($data)
        ->assertPassesValidation();
});
