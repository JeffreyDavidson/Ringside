<?php

declare(strict_types=1);
use App\Actions\Storylines\SaveParticipantAction;
use App\Data\Storylines\ParticipantData;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\actingAs;

test('ending and rejoining preserve dated creative involvement', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $joined = today()->subDays(4)->toDateString();
    $left = today()->subDays(1)->toDateString();

    // Act
    $participant = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, 'Rival', $joined));
    resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $wrestler->id, 'Rival', $joined, $left, 'Story moved on.'), $participant->id);
    $rejoined = resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $wrestler->id, 'Partner', today()->toDateString()));

    // Assert
    expect(requiredDate($participant->refresh()->left_on)->toDateString())->toBe($left)
        ->and($rejoined->id)->not->toBe($participant->id)
        ->and($story->participants()->count())->toBe(2);
});

test('teams participate creatively and duplicate current involvement is refused', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $team = TagTeam::factory()->for(requiredModel($story->promotion))->create();
    $data = new ParticipantData('tag_team', $team->id, 'Allies', today()->toDateString());
    resolve(SaveParticipantAction::class)->handle($story, 1, $data);
    expect(fn () => resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, $data))->toThrow(StorylineConflictException::class);
});
test('participants are stored under their morph alias', function (string $type, string $factory) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $person = $factory::factory()->for(requiredModel($story->promotion))->create();

    // Act
    $participant = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData($type, $person->id, null, today()->toDateString()));

    // Assert
    expect($participant->participant_type)->toBe($type)
        ->and($participant->participant_id)->toBe($person->id)
        ->and($participant->label)->toBe($person->name);
})->with([
    'wrestler' => ['wrestler', Wrestler::class],
    'tag team' => ['tag_team', TagTeam::class],
]);

test('a person from another promotion or an unsupported kind is refused and nothing is written', function (string $type, Closure $person) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $personId = $person(requiredModel($story->promotion));

    // Act
    $attempt = fn () => resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData($type, $personId, null, today()->toDateString()));

    // Assert
    expect($attempt)->toThrow(ModelNotFoundException::class)
        ->and($story->participants()->count())->toBe(0)
        ->and($story->refresh()->version)->toBe(1);
})->with([
    'wrestler of another promotion' => ['wrestler', fn (Promotion $home): int => Wrestler::factory()->for(Promotion::factory())->create()->id],
    'a wrestler id read as a tag team' => ['tag_team', fn (Promotion $home): int => Wrestler::factory()->for($home)->create()->id],
    // A tag team id, so the refusal can only come from the kind check and not from a missing row.
    'a kind outside the morph map' => ['nonsense', fn (Promotion $home): int => TagTeam::factory()->for($home)->create()->id],
    'a mapped kind that cannot take part' => ['manager', fn (Promotion $home): int => TagTeam::factory()->for($home)->create()->id],
]);

test('closed or mismatched participation cannot be rewritten', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $one = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $two = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $date = today()->toDateString();
    $row = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $one->id, null, $date));
    expect(fn () => resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $two->id, null, $date, $date, 'Wrong person.'), $row->id))->toThrow(StorylineConflictException::class);
    $one->delete();
    resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $one->id, null, $date, $date, 'Retired from this story.'), $row->id);
    expect(fn () => resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $one->id, null, $date, $date, 'Repeated.'), $row->id))->toThrow(StorylineConflictException::class);
});

test('ending participation cannot backdate the stored joining date to disguise an invalid end', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, null, today()->toDateString()));
    expect(fn () => resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $wrestler->id, null, today()->subDays(4)->toDateString(), today()->subDay()->toDateString(), 'Backdated.'), $person->id))->toThrow(StorylineConflictException::class);
});
