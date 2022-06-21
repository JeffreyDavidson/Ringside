<?php

use App\Http\Requests\TagTeams\StoreRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Tests\RequestFactories\TagTeamRequestFactory;

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

test('tag team name is required', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('tag team name must be a string', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('tag team name must be at least 3 characters', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('tag team name must be unique', function () {
    TagTeam::factory()->create(['name' => 'Example Tag Team Name']);

    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'name' => 'Example Tag Team Name',
        ]))
        ->assertFailsValidation(['name' => 'unique:tag_teams,name,NULL,id']);
});

test('tag team signature move is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'signature_move' => null,
        ]))
        ->assertPassesValidation();
});

test('tag team signature move must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'signature_move' => 12345,
        ]))
        ->assertFailsValidation(['signature_move' => 'string']);
});

test('tag team started at is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'started_at' => null,
        ]))
        ->assertPassesValidation();
});

test('tag team started at must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'started_at' => 12345,
        ]))
        ->assertFailsValidation(['started_at' => 'string']);
});

test('tag team started at must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'started_at' => 'not-a-date',
        ]))
        ->assertFailsValidation(['started_at' => 'date']);
});

test('tag team wrestlers are optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => [],
        ]))
        ->assertPassesValidation();
});

test('tag team wrestlers must be an array if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => 'not-an-array',
        ]))
        ->assertFailsValidation(['wrestlers' => 'array']);
});

test('tag team wrestlers is required with a tag team signature move', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => null,
            'signature_move' => 'Example Signature Move',
        ]))
        ->assertFailsValidation(['wrestlers' => 'requiredwith:signature_move']);
});

test('each tag team wrestler must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => ['not-an-integer'],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'integer']);
});

test('each tag team wrestler must be distinct', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => [1, 1],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'distinct']);
});

test('each tag team wrestler must exist', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => [1, 2],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'exist']);
});

test('each tag team wrestler cannot be suspended to join a tag team', function () {
    $wrestlerA = Wrestler::factory()->suspended()->create();
    $wrestlerB = Wrestler::factory()->bookable()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => [$wrestlerA->id, $wrestlerB->id],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'cannot_be_suspended_to_join_tag_team']);
});

test('each tag team wrestler cannot be injured to join a tag team', function () {
    $wrestlerA = Wrestler::factory()->injured()->create();
    $wrestlerB = Wrestler::factory()->bookable()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => [$wrestlerA->id, $wrestlerB->id],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'cannot_be_injured_to_join_tag_team']);
});

test('each tag team wrestler cannot join multiple bookable tag team', function () {
    $tagTeam = TagTeam::factory()
        ->bookable()
        ->has(Wrestler::factory()->bookable()->count(2))
        ->bookable()
        ->create();

    $wrestlerB = Wrestler::factory()
        ->bookable()
        ->create();

    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlers' => [$tagTeam->currentWrestlers->first()->getKey(), $wrestlerB->getKey()],
        ]))
        ->assertFailsValidation(['wrestlers.0' => 'cannot_belong_to_multiple_employed_tag_teams']);
});
