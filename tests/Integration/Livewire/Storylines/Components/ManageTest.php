<?php

declare(strict_types=1);
use App\Actions\Storylines\SaveParticipantAction;
use App\Data\Storylines\ParticipantData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Storylines\Components\Manage;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use App\Services\Promotions\PromotionContextService;

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
    $page->call('begin', 'participant-end', $person->id)->set('form.reason', 'Left the story.')->call('save')->assertHasNoErrors();
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
    $page->call('begin', 'record-step', $step->id)->set('form.heading', 'Actual angle')->set('form.description', 'A different promo.')->call('save')->assertHasNoErrors()->assertSee('Actual angle');
    $development = $f['story']->developments()->firstOrFail();
    $page->call('begin', 'development', $development->id)->set('form.reason', 'Clarified wording.')->call('save')->assertHasNoErrors();
    $page->call('begin', 'retract', $development->id)->set('form.reason', 'It was a rehearsal.')->set('form.reopenStep', true)->call('save')->assertHasNoErrors();
    $page->call('begin', 'record-step', $step->id)->set('form.reason', 'Reinstating the corrected account.')->call('save')->assertHasNoErrors();
});
test('a confirmed cast can be corrected through the form without rewriting names', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create(['name' => 'Harbor Rival']);
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, null, today()->toDateString()));
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
    $page->call('begin', 'step')->call('cancel')->assertSet('mode', '');
});
test('step and story statuses are explicit forms with retained reasons', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $page = livewire(Manage::class, ['storylineId' => $story->id]);
    $page->call('begin', 'step-status', $step->id)->set('form.status', 'postponed')->set('form.reason', 'Wait for talent.')->call('save')->assertHasNoErrors();
    $page->call('begin', 'story-status')->set('form.status', 'paused')->set('form.reason', 'Wait for tour.')->call('save')->assertHasNoErrors()->assertSee('Paused');
    $page->call('begin', 'story-status')->set('form.status', 'archived')->set('form.reason', 'Save for later.')->call('save')->assertHasNoErrors();
    $page->call('begin', 'story-status')->set('form.status', 'paused')->set('form.reason', 'Restore.')->call('save')->assertHasNoErrors();
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
