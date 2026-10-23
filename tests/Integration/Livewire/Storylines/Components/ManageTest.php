<?php

declare(strict_types=1);
use App\Actions\Storylines\SaveParticipantAction;
use App\Data\Storylines\ParticipantData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Storylines\StorylineFormMode;
use App\Enums\Storylines\StorylineStatus;
use App\Livewire\Storylines\Components\Manage;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use App\Services\Promotions\PromotionContextService;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('a booker can add an undated next step without booking a match', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    // Act
    $page->call('begin', 'step');
    $page->set('form.heading', 'The betrayal')->set('form.description', 'The partner turns after the bell.');
    $page->call('save');
    // Assert
    $page->assertHasNoErrors()->assertSee('The betrayal')->assertSee('Next planned');
    expect($story->steps()->count())->toBe(1)->and(EventMatch::query()->count())->toBe(0);
});

require_once __DIR__.'/../../../../Fixtures/storylines.php';
test('a booker can revise a premise and manage creative involvement with bounded searches', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create(['name' => 'Harbor Rival']);
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'story')->set('form.heading', 'Changed story')->set('form.description', 'A new direction.')->set('form.reason', 'The plan evolved.')->call('save')->assertHasNoErrors()->assertSee('Changed story');
    $page->call('begin', 'participant');
    $result = $page->instance()->searchRoster('wrestlers', 'Harbor');
    expect(array_column($result, 'id'))->toBe([$wrestler->id]);
    $page->set('form.participantId', $wrestler->id)->set('form.joinedOn', today()->toDateString())->set('form.role', 'Rival')->call('save')->assertHasNoErrors()->assertSee('Harbor Rival');
    $person = $story->participants()->firstOrFail();
    $page->call('begin', 'participant_end', $person->id)->set('form.reason', 'Left the story.')->call('save')->assertHasNoErrors();
    expect($person->refresh()->left_on)->not->toBeNull();
});
test('source selections change explicitly and an event can be linked to a match or segment', function () {
    $f = storylineSourceFixture();
    $page = livewire(Manage::class, ['storylineId' => $f['story']->id]);
    $page->call('begin', 'step')->set('eventSearch', 'Harbor')->set('form.heading', 'Linked plan')->set('form.description', 'A confrontation.')->set('form.eventId', $f['event']->id);
    $page->set('form.matchId', $f['match']->id)->assertSee('Result not recorded');
    $page->set('form.segmentId', $f['segment'])->assertSet('form.matchId', null);
    $page->call('save')->assertHasNoErrors()->assertSee('Closing promo');
    $step = $f['story']->steps()->firstOrFail();
    $page->call('begin', 'step', $step->id)->set('form.reason', 'Refined wording.')->call('save')->assertHasNoErrors();
    $page->call('begin', 'record_step', $step->id)->set('form.heading', 'Actual angle')->set('form.description', 'A different promo.')->call('save')->assertHasNoErrors()->assertSee('Actual angle');
    $development = $f['story']->developments()->firstOrFail();
    $page->call('begin', 'development', $development->id)->set('form.reason', 'Clarified wording.')->call('save')->assertHasNoErrors();
    $page->call('begin', 'retract', $development->id)->set('form.reason', 'It was a rehearsal.')->set('form.reopenStep', true)->call('save')->assertHasNoErrors();
    $page->call('begin', 'record_step', $step->id)->set('form.reason', 'Reinstating the corrected account.')->call('save')->assertHasNoErrors();
});
test('a confirmed cast can be corrected through the form without rewriting names', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create(['name' => 'Harbor Rival']);
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, null, today()->toDateString()), signedInUser());
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'development')->set('form.heading', 'The angle')->set('form.description', 'The argument.')->set('form.participantIds', [$person->id])->call('save')->assertHasNoErrors();
    $development = $story->developments()->firstOrFail();
    $page->call('begin', 'development', $development->id)->assertSet('form.participantIds', [$person->id])->set('form.reason', 'Clarified.')->call('save')->assertHasNoErrors();
});
test('stale forms preserve text and require deliberate reload before a second attempt', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'step')->set('form.heading', 'Keep my draft')->set('form.description', 'Draft description.');
    plannedStorylineStep($story);
    $page->call('save')->assertHasErrors('storyline')->assertSet('needsReload', true)->assertSet('form.heading', 'Keep my draft');
    $page->call('save');
    expect($story->steps()->count())->toBe(1);
    $page->call('reload')->assertSet('needsReload', false)->call('save')->assertHasNoErrors();
    expect($story->steps()->count())->toBe(2);
    $page->call('begin', 'step')->call('cancel')->assertSet('mode', null);
});
test('step and story statuses are explicit forms with retained reasons', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'step_status', $step->id)->set('form.status', 'postponed')->set('form.reason', 'Wait for talent.')->call('save')->assertHasNoErrors();
    $page->call('begin', 'story_status')->set('form.status', 'paused')->set('form.reason', 'Wait for tour.')->call('save')->assertHasNoErrors()->assertSee('Paused');
    $page->call('begin', 'story_status')->set('form.status', 'archived')->set('form.reason', 'Save for later.')->call('save')->assertHasNoErrors();
    $page->call('begin', 'story_status')->set('form.status', 'paused')->set('form.reason', 'Restore.')->call('save')->assertHasNoErrors();
});
test('changing role while the form is open refuses saving and leaks no new story data', function () {
    $promotion = Promotion::factory()->create();
    $story = Storyline::factory()->for($promotion)->create();
    $manager = basicUser();
    $promotion->users()->attach($manager, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($manager);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'step');
    $promotion->users()->updateExistingPivot($manager->id, ['role' => MembershipRole::Member->value]);
    $context->forgetMemberships();
    $page->call('save')->assertForbidden();
    expect($story->steps()->count())->toBe(0);
});

test('a standalone target can be retimed and a selected event stays visible when its search term changes', function () {
    $f = storylineSourceFixture();
    $page = livewire(Manage::class, ['storylineId' => $f['story']->id]);
    $page->call('begin', 'step')->set('form.heading', 'Future payoff')->set('form.description', 'The eventual confrontation.');
    $page->set('form.targetOn', today()->addDays(21)->toDateString())->call('save')->assertHasNoErrors();
    $page->call('begin', 'step')->set('form.eventId', $f['event']->id)->set('eventSearch', 'No such event')->assertSee('Harbor Show');
});

test('switching the creative participant type clears the old identity before saving', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'participant')->set('form.participantId', $wrestler->id)->set('form.type', 'tag_team')->assertSet('form.participantId', null);
});

test('a form that breaks a field rule saves nothing and names the field', function (string $mode, array $fields, string $error) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', $mode);
    $page->set('form.heading', 'A heading')->set('form.description', 'A description.')->set('form.reason', 'A reason.');
    $page->set('form.joinedOn', today()->toDateString())->set('form.participantId', 1);

    foreach ($fields as $field => $value) {
        $page->set("form.{$field}", $value);
    }

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors([$error]);
    expect($story->refresh()->version)->toBe(1)->and($story->revisions()->count())->toBe(0);
})->with([
    'story without a name' => ['story', ['heading' => ''], 'form.heading'],
    'story with an overlong premise' => ['story', ['description' => str_repeat('a', 4001)], 'form.description'],
    'story with an overlong payoff' => ['story', ['payoff' => str_repeat('a', 4001)], 'form.payoff'],
    'story without a reason' => ['story', ['reason' => ''], 'form.reason'],
    'step without a heading' => ['step', ['heading' => ''], 'form.heading'],
    'step with an overlong heading' => ['step', ['heading' => str_repeat('a', 161)], 'form.heading'],
    'step without a description' => ['step', ['description' => ''], 'form.description'],
    'step with a sequence of zero' => ['step', ['sequence' => 0], 'form.sequence'],
    'step with an overlong reason' => ['step', ['reason' => str_repeat('a', 2001)], 'form.reason'],
    'development without a heading' => ['development', ['heading' => ''], 'form.heading'],
    'development with a malformed date' => ['development', ['occurredOn' => '13/10/2026'], 'form.occurredOn'],
    'development with a sequence over the limit' => ['development', ['sequence' => 10001], 'form.sequence'],
    'development with a repeated cast member' => ['development', ['participantIds' => [4, 4]], 'form.participantIds.0'],
    'participant with an unknown kind' => ['participant', ['type' => 'manager'], 'form.type'],
    'participant without a roster identity' => ['participant', ['participantId' => null], 'form.participantId'],
    'participant with a malformed joining date' => ['participant', ['joinedOn' => 'yesterday'], 'form.joinedOn'],
    'participant with an overlong role' => ['participant', ['role' => str_repeat('a', 161)], 'form.role'],
]);

test('a future actual date is refused before anything is recorded', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'development');
    $page->set('form.heading', 'Future')->set('form.description', 'Not yet happened.')->set('form.occurredOn', today()->addDays(2)->toDateString());

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors(['form.occurredOn']);
    expect($story->developments()->count())->toBe(0);
});

test('corrections, endings and status changes need a reason', function (string $mode) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $development = confirmedStorylineDevelopment($story);
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $person = resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $wrestler->id, null, today()->toDateString()), signedInUser());
    $version = $story->refresh()->version;
    $id = ['step_status' => $step->id, 'retract' => $development->id, 'development' => $development->id, 'participant_end' => $person->id, 'step' => $step->id][$mode] ?? null;
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', $mode, $id)->set('form.status', 'paused')->set('form.reason', '');

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors(['form.reason']);
    expect($story->refresh()->version)->toBe($version);
})->with(['step', 'development', 'participant_end', 'step_status', 'story_status', 'retract']);

test('ending an involvement cannot precede its joining date', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, null, today()->toDateString()), signedInUser());
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'participant_end', $person->id)->set('form.leftOn', today()->subDay()->toDateString())->set('form.reason', 'Backdated.');

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors(['form.leftOn']);
    expect($person->refresh()->left_on)->toBeNull();
});

test('a manager downgraded while a form is open writes nothing from any form', function (string $mode) {
    // Arrange
    $promotion = Promotion::factory()->create();
    $story = Storyline::factory()->for($promotion)->create();
    $manager = basicUser();
    $promotion->users()->attach($manager, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($manager);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $step = plannedStorylineStep($story);
    $development = confirmedStorylineDevelopment($story);
    $wrestler = Wrestler::factory()->for($promotion)->create();
    $person = resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $wrestler->id, null, today()->toDateString()), signedInUser());
    $version = $story->refresh()->version;
    $id = ['step_status' => $step->id, 'retract' => $development->id, 'development' => $development->id, 'participant_end' => $person->id, 'step' => $step->id][$mode] ?? null;
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', $mode, $id)->set('form.heading', 'Downgraded')->set('form.description', 'Should not save.')->set('form.reason', 'Reason.')->set('form.status', $mode === 'step_status' ? 'postponed' : 'paused')->set('form.sequence', 7);

    if ($mode === 'participant') {
        $page->set('form.participantId', Wrestler::factory()->for($promotion)->create()->id);
    }
    $promotion->users()->updateExistingPivot($manager->id, ['role' => MembershipRole::Member->value]);
    $context->forgetMemberships();

    // Act
    $page->call('save');

    // Assert
    $page->assertForbidden();
    expect($story->refresh()->version)->toBe($version)->and($story->status)->toBe(StorylineStatus::Active)->and($step->refresh()->version)->toBe(1);
})->with(['story', 'step', 'development', 'participant', 'participant_end', 'step_status', 'story_status', 'retract']);

test('an unknown form mode from the request is a 404 and opens nothing', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);

    // Act
    $page->call('begin', 'not-a-mode');

    // Assert
    $page->assertNotFound();
});

test('saving with no form open is a 404 and writes nothing', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);

    // Act
    $page->call('save');

    // Assert
    $page->assertNotFound();
    expect($story->refresh()->version)->toBe(1)->and($story->revisions()->count())->toBe(0);
});

test('the open form mode cannot be changed from the client', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'step');

    // Act
    $page->set('mode', 'story_status');
})->throws(CannotUpdateLockedPropertyException::class);

test('ending an involvement opens the form filled from the stored period', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $joined = today()->subDays(3)->toDateString();
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, 'Rival', $joined), signedInUser());
    $page = livewire(Manage::class, ['storylineId' => $story->id]);

    // Act
    $page->call('begin', 'participant_end', $person->id);

    // Assert
    $page->assertSet('form.participantId', $wrestler->id)
        ->assertSet('form.type', 'wrestler')
        ->assertSet('form.joinedOn', $joined)
        ->assertSet('form.leftOn', today()->toDateString())
        ->assertSet('form.role', 'Rival')
        ->assertSet('editingId', $person->id);
});

test('editing the premise opens the form filled from the storyline', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create(['payoff' => 'The hidden finish.']);
    $page = livewire(Manage::class, ['storylineId' => $story->id]);

    // Act
    $page->call('begin', 'story');

    // Assert
    $page->assertSet('form.heading', $story->title)
        ->assertSet('form.description', $story->premise)
        ->assertSet('form.payoff', 'The hidden finish.')
        ->assertSet('editingId', null);
});

test('recording a step opens a new account filled from the step', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $page = livewire(Manage::class, ['storylineId' => $story->id]);

    // Act
    $page->call('begin', 'record_step', $step->id);

    // Assert
    $page->assertSet('form.heading', $step->heading)
        ->assertSet('form.description', $step->description)
        ->assertSet('form.sequence', $step->sequence)
        ->assertSet('stepId', $step->id)
        ->assertSet('editingId', null)
        ->assertSet('mode', StorylineFormMode::Development);
});

test('recording a step that already has an account opens that account for correction', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $development = confirmedStorylineDevelopment($story, $step->id);
    $page = livewire(Manage::class, ['storylineId' => $story->id]);

    // Act
    $page->call('begin', 'record_step', $step->id);

    // Assert
    $page->assertSet('form.heading', $development->heading)
        ->assertSet('form.occurredOn', $development->occurred_on->toDateString())
        ->assertSet('stepId', $step->id)
        ->assertSet('editingId', $development->id)
        ->assertSet('mode', StorylineFormMode::Development);
});

test('correcting an account opens the form on its step', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $development = confirmedStorylineDevelopment($story, $step->id);
    $page = livewire(Manage::class, ['storylineId' => $story->id]);

    // Act
    $page->call('begin', 'development', $development->id);

    // Assert
    $page->assertSet('form.heading', $development->heading)
        ->assertSet('stepId', $step->id)
        ->assertSet('editingId', $development->id);
});
