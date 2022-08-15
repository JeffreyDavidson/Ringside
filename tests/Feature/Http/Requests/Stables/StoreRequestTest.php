<?php

use App\Http\Requests\Stables\StoreRequest;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\StableRequestFactory;

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

test('stable name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('stable name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('stable name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('stable name must be unique', function () {
    Stable::factory()->create(['name' => 'Example Stable Name']);

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'name' => 'Example Stable Name',
        ]))
        ->assertFailsValidation(['name' => 'unique:stables,name,NULL,id']);
});

test('stable start date is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'start_date' => null,
        ]))
        ->assertPassesValidation();
});

test('stable start date must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'start_date' => 12345,
        ]))
        ->assertFailsValidation(['start_date' => 'string']);
});

test('stable start date must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'start_date' => 'not-a-date',
        ]))
        ->assertFailsValidation(['start_date' => 'date']);
});

test('stable wreslters must be an array', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => 'not-an-array',
        ]))
        ->assertFailsValidation(['wrestlers' => 'array']);
});

test('stable tag teams must be an array', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => 'not-an-array',
        ]))
        ->assertFailsValidation(['tag_teams' => 'array']);
});

test('each wrestler in a stable must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => ['not-an-integer'],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'integer']);
});

test('each wrestler in a stable must be distinct', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => [1, 1],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'distinct']);
});

test('each wrestler in a stable must exist', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => [1, 2],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'exists']);
});

test('each wrestler must not already be in stable to join stable', function () {
    $wrestlerAlreadyInDifferentStable = Wrestler::factory()->bookable()->create();
    Stable::factory()
        ->hasAttached($wrestlerAlreadyInDifferentStable, ['joined_at' => now()->toDateTimeString()])
        ->create();
    $wrestlerNotInStableA = Wrestler::factory()->bookable()->create();
    $wrestlerNotInStableB = Wrestler::factory()->bookable()->create();
    $wrestlerNotInStableC = Wrestler::factory()->bookable()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => [
                $wrestlerAlreadyInDifferentStable->getKey(),
                $wrestlerNotInStableA->getKey(),
                $wrestlerNotInStableB->getKey(),
                $wrestlerNotInStableC->getKey(),
            ],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'app\rules\wrestlercanjoinnewstable']);
});

test('each tag team in a stable must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => ['not-an-integer'],
        ]))
        ->assertFailsValidation(['tag_teams.0' => 'integer']);
});

test('each tag team in a stable must be distinct', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => [1, 1],
        ]))
        ->assertFailsValidation(['tag_teams.0' => 'distinct']);
});

test('each tag team in a stable must exist', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => [1],
        ]))
        ->assertFailsValidation(['tag_teams.0' => 'exists']);
});

test('each tag team must not already be in stable to join stable', function () {
    $tagTeamAlreadyInDifferentStable = TagTeam::factory()->bookable()->create();
    $stable = Stable::factory()
        ->hasAttached($tagTeamAlreadyInDifferentStable, ['joined_at' => now()->toDateTimeString()])
        ->create();
    $tagTeamNotInStableA = TagTeam::factory()->bookable()->create();
    $tagTeamNotInStableB = TagTeam::factory()->bookable()->create();
    $tagTeamNotInStableC = TagTeam::factory()->bookable()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => [
                $tagTeamAlreadyInDifferentStable->getKey(),
                $tagTeamNotInStableA->getKey(),
                $tagTeamNotInStableB->getKey(),
                $tagTeamNotInStableC->getKey(),
            ],
        ]))
        ->assertFailsValidation(['tag_teams.0' => 'app\rules\tagteamcanjoinnewstable']);
});

test('stable must have a minimum number of 3 members when start date is set', function () {
    $wrestlers = Wrestler::factory()->count(2)->create();

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'members_count' => $wrestlers->count(),
            'start_date' => Carbon::now()->toDateTimeString(),
            'wrestlers' => $wrestlers->modelKeys(),
            'tag_teams' => [],
        ]))
        ->assertFailsValidation(['members_count' => 'min:3']);
});

test('stable can have one wrestler and one tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'members_count' => 3,
            'start_date' => Carbon::now()->toDateTimeString(),
            'wrestlers' => [$wrestler->id],
            'tag_teams' => [$tagTeam->id],
        ]))
        ->assertPassesValidation();
});

test('a stable cannot be formed with only one tag team and no wrestlers', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'members_count' => 2,
            'start_date' => Carbon::now()->toDateTimeString(),
            'wrestlers' => [],
            'tag_teams' => [$tagTeam->id],
        ]))
        ->assertFailsValidation(['members_count' => 'min:3']);
});

test('a stable can contain at least two tag teams with no wrestlers', function () {
    $tagTeamA = TagTeam::factory()->create();
    $tagTeamB = TagTeam::factory()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'members_count' => 4,
            'start_date' => Carbon::now()->toDateTimeString(),
            'wrestlers' => [],
            'tag_teams' => [$tagTeamA->id, $tagTeamB->id],
        ]))
        ->assertPassesValidation();
});

test('a stable can contain at least three wrestlers with no tag teams', function () {
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();
    $wrestlerC = Wrestler::factory()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'members_count' => 3,
            'start_date' => Carbon::now()->toDateTimeString(),
            'wrestlers' => [$wrestlerA->id, $wrestlerB->id, $wrestlerC->id],
            'tag_teams' => [],
        ]))
        ->assertPassesValidation();
});

test('a stable cannot contain a wrestler that is added in a tag team', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(StableRequestFactory::new()->create([
            'members_count' => 5,
            'start_date' => Carbon::now()->toDateTimeString(),
            'wrestlers' => [
                $wrestlerA->getKey(),
                $wrestlerB->getKey(),
                $tagTeam->currentWrestlers->first()->getKey(),
            ],
            'tag_teams' => [$tagTeam->getKey()],
        ]))
        ->assertFailsValidation(['wrestlers.2' => 'app\rules\wrestlercanjoinnewstable']);
});
