<?php

declare(strict_types=1);

use App\Http\Requests\TagTeams\StoreRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Rules\WrestlerCanJoinNewTagTeam;
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

test('tag team start date is optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'start_date' => null,
        ]))
        ->assertPassesValidation();
});

test('tag team start date must be a string if provided', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'start_date' => 12345,
        ]))
        ->assertFailsValidation(['start_date' => 'string']);
});

test('tag team start date must be in the correct date format', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'start_date' => 'not-a-date',
        ]))
        ->assertFailsValidation(['start_date' => 'date']);
});

test('tag team wrestlers are optional', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlerA' => null,
            'wrestlerB' => null,
        ]))
        ->assertPassesValidation();
});

test('tag team wrestlers is required with a tag team signature move', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlerA' => null,
            'wrestlerB' => null,
            'signature_move' => 'Example Signature Move',
        ]))
        ->assertFailsValidation(['wrestlerA' => 'required_with:signature_move']);
});

test('each tag team wrestler must be an integer', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlerA' => 'not-an-integer',
            'wrestlerB' => 'not-an-integer',
        ]))
        ->assertFailsValidation(['wrestlerA' => 'integer', 'wrestlerB' => 'integer']);
});

test('each tag team wrestler must be distinct', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlerA' => 1,
            'wrestlerB' => 1,
        ]))
        ->assertFailsValidation(['wrestlerA' => 'different:wrestlerB', 'wrestlerB' => 'different:wrestlerA']);
});

test('each tag team wrestler must exist', function () {
    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlerA' => 1,
            'wrestlerB' => 2,
        ]))
        ->assertFailsValidation(['wrestlerA' => 'exists', 'wrestlerB' => 'exists']);
});

test('each tag team wrestler cannot be suspended to join a tag team', function () {
    $wrestlerA = Wrestler::factory()->suspended()->create();
    $wrestlerB = Wrestler::factory()->bookable()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlerA' => $wrestlerA->id,
            'wrestlerB' => $wrestlerB->id,
        ]))
        ->assertFailsValidation(['wrestlerA' => WrestlerCanJoinNewTagTeam::class]);
});

test('each tag team wrestler cannot be injured to join a tag team', function () {
    $wrestlerA = Wrestler::factory()->injured()->create();
    $wrestlerB = Wrestler::factory()->bookable()->create();

    $this->createRequest(StoreRequest::class)
        ->validate(TagTeamRequestFactory::new()->create([
            'wrestlerA' => $wrestlerA->id,
            'wrestlerB' => $wrestlerB->id,
        ]))
        ->assertFailsValidation(['wrestlerA' => WrestlerCanJoinNewTagTeam::class]);
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
            'wrestlerA' => $tagTeam->currentWrestlers->first()->getKey(),
            'wrestlerB' => $wrestlerB->getKey(),
        ]))
        ->assertFailsValidation(['wrestlerA' => WrestlerCanJoinNewTagTeam::class]);
});
