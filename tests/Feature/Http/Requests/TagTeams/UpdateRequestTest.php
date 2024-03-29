<?php

declare(strict_types=1);

use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\Employment;
use App\Models\TagTeam;
use App\Rules\EmploymentStartDateCanBeChanged;
use Illuminate\Support\Carbon;
use Tests\RequestFactories\TagTeamRequestFactory;

test('an administrator is authorized to make this request', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->by(administrator())
        ->assertAuthorized();
});

test('a non administrator is not authorized to make this request', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->by(basicUser())
        ->assertNotAuthorized();
});

test('tag team name is required', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'name' => null,
        ]))
        ->assertFailsValidation(['name' => 'required']);
});

test('tag team name must be a string', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'name' => 123,
        ]))
        ->assertFailsValidation(['name' => 'string']);
});

test('tag team name must be at least 3 characters', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'name' => 'ab',
        ]))
        ->assertFailsValidation(['name' => 'min:3']);
});

test('tag team name must be unique', function () {
    $tagTeamA = TagTeam::factory()->create(['name' => 'Example Tag Team Name A']);
    TagTeam::factory()->create(['name' => 'Example Tag Team Name B']);

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeamA)
        ->validate(TagTeamRequestFactory::new()->create([
            'name' => 'Example Tag Team Name B',
        ]))
        ->assertFailsValidation(['name' => 'unique:tag_teams,NULL,'.$tagTeamA->id.',id']);
});

test('tag team signature move is optional if wrestlers are not provided', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'signature_move' => null,
            'wrestlers' => [],
        ]))
        ->assertPassesValidation();
});

test('tag team signature move must be a string if provided', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'signature_move' => 12345,
        ]))
        ->assertFailsValidation(['signature_move' => 'string']);
});

test('tag team start date is optional', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'start_date' => null,
        ]))
        ->assertPassesValidation();
});

test('tag team start date must be a string if provided', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'start_date' => 12345,
        ]))
        ->assertFailsValidation(['start_date' => 'string']);
});

test('tag team start date must be in the correct date format', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'start_date' => 'not-a-date-format',
        ]))
        ->assertFailsValidation(['start_date' => 'date']);
});

test('tag team start date cannot be changed if employment start date has past', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'start_date' => Carbon::now()->toDateTImeString(),
        ]))
        ->assertFailsValidation(['start_date' => EmploymentStartDateCanBeChanged::class]);
});

test('tag_team_start_date_can_be_changed_if_employment_start_date_is_in_the_future', function () {
    $tagTeam = TagTeam::factory()->has(Employment::factory()->started(Carbon::parse('+2 weeks')))->create();

    $this->createRequest(UpdateRequest::class)
        ->withParam('tag_team', $tagTeam)
        ->validate(TagTeamRequestFactory::new()->create([
            'start_date' => Carbon::tomorrow()->toDateString(),
        ]))
        ->assertPassesValidation();
});
