<?php

use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\StableRequestFactory;

test('an administrator is authorized to make this request', function () {
    $this->createRequest(UpdateRequest::class)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $this->createRequest(UpdateRequest::class)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('stable name is required', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('stable name must be a string', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('stable name must be at least 3 characters', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('stable name must be unique', function () {
    $stableA = Stable::factory()->create();
    Stable::factory()->create(['name' => 'Example Stable']);

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stableA)
        ->validate(StableRequestFactory::new()->create([
            'name' => 'Example Stable',
        ]))
        ->assertFailsValidation(['name' => 'unique:stables,NULL,1,id']);
});

test('stable started at is optional if not activated', function () {
    $stable = Stable::factory()->unactivated()->withNoMembers()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'started_at' => null,
        ]))
        ->assertPassesValidation();
});

test('stable started at is required if active', function () {
    $stable = Stable::factory()->active()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'started_at' => null,
        ]))
        ->assertFailsValidation(['started_at' => 'required']);
});

test('stable started must be a string if provided', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'started_at' => 12345,
        ]))
        ->assertFailsValidation(['started_at' => 'string']);
});

test('stable started at must be in the correct date format', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'started_at' => 'not-a-date-format',
        ]))
        ->assertFailsValidation(['started_at' => 'date']);
});

test('stable started at cannot be changed if stable date has past', function () {
    $stable = Stable::factory()->active()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'started_at' => Carbon::now()->toDateTimeString(),
        ]))
        ->assertFailsValidation(['started_at' => 'app\rules\stables\activationstartdatecanbechanged']);
});

test('stable started at can be changed if activation start date is in the future', function () {
    $stable = Stable::factory()->withFutureActivation()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'started_at' => Carbon::tomorrow()->toDateString(),
        ]))
        ->assertPassesValidation();
});

test('stable wrestlers must be an array', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => 'not-an-array',
        ]))
        ->assertFailsValidation(['wrestlers' => 'array']);
});

test('stable tag teams must be an array', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => 'not-an-array',
        ]))
        ->assertFailsValidation(['tag_teams' => 'array']);
});

test('each wrestler in a stable must be an integer', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => ['not-an-integer'],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'integer']);
});

test('each wrestler in a stable must be distinct', function () {
    $stable = Stable::factory()
        ->hasAttached(Wrestler::factory()->count(2), ['joined_at' => now()->toDateTimeString()])
        ->create();
    $currentWrestlers = $stable->currentWrestlers;

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => [$currentWrestlers->first()->id, $currentWrestlers->first()->id],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'distinct']);
});

test('each wrestler in a stable must exist', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => [1, 2],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'exists']);
});

test('a suspended wrestler cannot join the stable', function () {
    $stable = Stable::factory()->withEmployedDefaultMembers()->create();
    $wrestlerNotInStable = Wrestler::factory()->suspended()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => [$wrestlerNotInStable->getKey()],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'app\rules\wrestlercanjoinexistingstable']);
});

test('an injured wrestler cannot join the stable', function () {
    $stable = Stable::factory()->withEmployedDefaultMembers()->create();
    $wrestlerNotInStable = Wrestler::factory()->injured()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'wrestlers' => [$wrestlerNotInStable->getKey()],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'app\rules\wrestlercanjoinexistingstable']);
});

test('each tag team in a stable must be an integer', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => ['not-an-integer'],
        ]))
        ->assertFailsValidation(['tag_teams.0' => 'integer']);
});

test('each tag team in a stable must be distinct', function () {
    $stable = Stable::factory()
        ->hasAttached(TagTeam::factory()->count(2), ['joined_at' => now()->toDateTimeString()])
        ->create();
    $currentTagTeams = $stable->currentTagTeams;

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => [1, 1],
        ]))
        ->assertFailsValidation(['tag_teams.0' => 'distinct']);
});

test('each tag team in a stable must exist', function () {
    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => [1, 2],
        ]))
        ->assertFailsValidation(['tag_teams.0' => 'exists']);
});

test('suspended tag teams cannot join a stable', function () {
    $stable = Stable::factory()
        ->hasAttached(TagTeam::factory(), ['joined_at' => now()->toDateTimeString()])
        ->create();
    $tagTeamToJoinStable = $stable->currentTagTeams->first();
    $tagTeamNotInStable = TagTeam::factory()->suspended()->create();

    $stable = Stable::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'tag_teams' => [$tagTeamNotInStable->getKey()],
        ]))
        ->assertFailsValidation(['tag_teams.0' => 'app\rules\tagteamcanjoinexistingstable']);
});

test('stable must have a minimum number of members', function () {
    $stable = Stable::factory()->withEmployedDefaultMembers()->create();
    $wrestlersToJoinStable = Wrestler::factory()->bookable()->count(2)->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('stable', $stable)
        ->validate(StableRequestFactory::new()->create([
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $wrestlersToJoinStable->modelKeys(),
            'tag_teams' => [],
        ]))
        ->assertFailsValidation(['members_count' => 'app\models\hasminimumamountofmembers']);
});
