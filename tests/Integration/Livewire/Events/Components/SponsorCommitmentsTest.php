<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../Fixtures/sponsor-commitment.php';

use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Actions\Events\SaveSponsorInstructionAction;
use App\Data\Events\SponsorInstructionData;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorInstructionMode;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Exceptions\RecordChangedException;
use App\Livewire\Events\Components\SponsorCommitments;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use App\Services\Promotions\PromotionContextService;
use JMac\Testing\Double;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('a saved form creates one private promise and resets the create draft', function (): void {
    // Arrange
    $event = Event::factory()->for(Promotion::factory())->create();
    $sponsor = Sponsor::factory()->create(['promotion_id' => $event->promotion_id]);
    actingAs(administrator());
    $component = livewire(SponsorCommitments::class, ['eventId' => $event->id]);
    $component->call('newCommitment');

    // Act
    $component->set('form.sponsorId', $sponsor->id)->set('form.type', 'banner')->set('form.description', 'Hang the entrance banner')->call('save');

    // Assert
    $component->assertHasNoErrors()->assertSee('Promise saved.')->assertSet('formShown', false);
    expect(EventSponsorCommitment::query()->count())->toBe(1)
        ->and(EventSponsorCommitment::query()->firstOrFail()->revisions()->count())->toBe(1);
});

test('correcting evidence preserves the exact recorded completion instant', function (): void {
    // Arrange
    $world = sponsorFixture();
    $original = now()->subMinute()->setSecond(37);
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key,
        sponsorPromiseData($world['promise'], ['status' => SponsorCommitmentStatus::Completed, 'evidence' => 'Original check', 'completedAt' => $original]));
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('edit', $promise->id);

    // Act
    $component->set('form.evidence', 'Recording reviewed')->set('form.reason', 'Correct the evidence note')->call('save');

    // Assert
    $component->assertHasNoErrors();
    expect($promise->refresh()->completed_at?->toIso8601String())->toBe($original->toIso8601String());
});

test('ambiguous and skipped local deadlines require a real offset', function (string $deadline): void {
    // Arrange
    $world = sponsorFixture();
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('newCommitment');

    // Act
    $component->set('form.sponsorId', $world['sponsor']->id)->set('form.description', 'Timed promise')->set('form.timezone', 'America/New_York')->set('form.deadline', $deadline)->call('save');

    // Assert
    $component->assertHasErrors('form.deadline')->assertSet('form.description', 'Timed promise');
    expect(EventSponsorCommitment::query()->count())->toBe(1);
})->with(['2026-11-01T01:30', '2026-03-08T02:30']);

test('an explicit offset records the intended repeated local hour', function (): void {
    // Arrange
    $world = sponsorFixture();
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('newCommitment');

    // Act
    $component->set('form.sponsorId', $world['sponsor']->id)->set('form.description', 'Timed promise')->set('form.timezone', 'America/New_York')->set('form.deadline', '2026-11-01T01:30')->set('form.deadlineOffset', '-05:00')->call('save');

    // Assert
    $component->assertHasNoErrors();
    expect(EventSponsorCommitment::query()->where('description', 'Timed promise')->firstOrFail()->deadline_at?->toIso8601String())->toBe('2026-11-01T06:30:00+00:00');
});

test('stale form submissions preserve input and do not add a revision', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('edit', $promise->id);
    app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['description' => 'Changed by another operator']));

    // Act
    $component->set('form.description', 'My unsaved draft')->call('save');

    // Assert
    $component->assertHasErrors(['save' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])->assertSet('form.description', 'My unsaved draft')->assertSet('formShown', true);
    expect($promise->refresh()->description)->toBe('Changed by another operator')->and($promise->revisions()->count())->toBe(2);
});

test('history and instruction previews use this event and deliberate saved wording', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key,
        sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']));
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);

    // Act
    $component->call('showHistory', $promise->id)->call('beginInstruction', $promise->id)->call('previewInstruction')->call('saveInstruction');

    // Assert
    $component->assertHasNoErrors()->assertSee('Reviewed instructions saved to the pack.');
    expect($world['event']->pack()->firstOrFail()->content['sponsor_tasks'] ?? [])->toHaveCount(1);
    $component->call('beginInstruction', $promise->id)->call('previewInstruction');
    $component->set('mode', 'remove')->assertSet('previewHash', '');
    $component->set('instructionReason', 'Sponsor withdrew')->call('previewInstruction')->call('saveInstruction')->assertHasNoErrors();
    expect($promise->refresh()->pack_key)->toBeNull();
    $component->call('cancelDraft')->set('filter', 'all')->assertHasNoErrors();
});

test('foreign sponsor selections and unsafe evidence links cannot be saved', function (): void {
    // Arrange
    $world = sponsorFixture();
    $foreign = Sponsor::factory()->create();
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('newCommitment');

    // Act and Assert
    $component->set('form.sponsorId', $foreign->id)->set('form.description', 'Guessed sponsor')->call('save')->assertHasErrors('sponsorId');
    $component->set('form.sponsorId', $world['sponsor']->id)->set('form.evidenceUrl', 'javascript:alert(1)')->call('save')->assertHasErrors('form.evidenceUrl');
    expect(EventSponsorCommitment::query()->count())->toBe(1);
});

test('archived and unowned events keep history without allowing new promises', function (): void {
    // Arrange
    $world = sponsorFixture();
    $world['event']->delete();
    $unowned = Event::factory()->create();

    // Act and Assert
    livewire(SponsorCommitments::class, ['eventId' => $world['event']->id])->call('newCommitment')->assertHasErrors('save');
    livewire(SponsorCommitments::class, ['eventId' => $unowned->id])->call('newCommitment')->assertHasErrors('save');
    expect(EventSponsorCommitment::query()->count())->toBe(1);
});

test('a role downgrade refuses an already open private form without changing the promise', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promotion = Promotion::query()->findOrFail($world['promise']->promotion_id);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('edit', $world['promise']->id);
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    $context->forgetMemberships();

    // Act
    $component->call('save');

    // Assert
    $component->assertForbidden();
    expect($world['promise']->refresh()->version)->toBe(1);
});

test('a role downgrade refuses an already previewed instruction without changing the pack', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors']));
    $promotion = Promotion::query()->findOrFail($promise->promotion_id);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('beginInstruction', $promise->id)->call('previewInstruction');
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    $context->forgetMemberships();

    // Act
    $component->call('saveInstruction');

    // Assert
    $component->assertForbidden();
    expect($world['event']->pack()->exists())->toBeFalse()
        ->and($promise->refresh()->version)->toBe(2);
});

test('choosing a sponsor resolves only current active records and keeps its identity in the draft', function (): void {
    // Arrange
    $world = sponsorFixture();
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('newCommitment');

    // Act
    $component->call('chooseSponsor', $world['sponsor']->id)->set('sponsorSearch', 'Harbor');

    // Assert
    $component->assertSet('form.sponsorId', $world['sponsor']->id)->assertSee('Harbor Hardware');
    $component->call('cancelDraft')->assertSet('formShown', false);
});

test('a stale instruction save keeps the reason and says so', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors']));
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('beginInstruction', $promise->id)
        ->call('previewInstruction')
        ->set('instructionReason', 'Moved to the entrance');
    $save = Double::for(SaveSponsorInstructionAction::class);
    $save->expects('handle')
        ->throws(RecordChangedException::changed());
    app()->instance(SaveSponsorInstructionAction::class, $save);

    // Act
    $component->call('saveInstruction');

    // Assert
    $component->assertHasErrors(['save' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.'])
        ->assertSet('instructionReason', 'Moved to the entrance');
    expect($world['event']->pack()->exists())->toBeFalse();
    $save->verify();
});

test('a stale instruction preview is refused while the current saved wording remains visible', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors']));
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('beginInstruction', $promise->id)->call('previewInstruction');
    app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 2, $promise->submission_key, sponsorPromiseData($promise, ['instruction' => 'Updated by another operator']));

    // Act
    $component->call('saveInstruction');

    // Assert
    $component->assertHasErrors('save')->assertSee('Destination changed. Preview again before saving.');
    expect($world['event']->pack()->exists())->toBeFalse();
});

test('an unknown instruction mode is refused before a preview is made', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key,
        sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']));
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('beginInstruction', $promise->id)
        ->set('mode', 'delete');

    // Act
    $component->call('previewInstruction');

    // Assert
    $component->assertHasErrors(['mode'])
        ->assertSet('previewHash', '');
});

test('the previewed target reaches the action as a parsed pack key', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key,
        sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']));
    $component = livewire(SponsorCommitments::class, ['eventId' => $world['event']->id]);
    $component->call('beginInstruction', $promise->id)
        ->call('previewInstruction')
        ->call('saveInstruction');
    $promise->refresh();
    $component->call('beginInstruction', $promise->id)
        ->call('previewInstruction')
        ->set('instructionReason', 'Moved');
    $received = null;
    $save = Double::for(SaveSponsorInstructionAction::class);
    $save->expects('handle')
        ->resolves(function (mixed ...$arguments) use (&$received): void {
            $received = $arguments[5] instanceof SponsorInstructionData ? $arguments[5] : null;
        });
    app()->instance(SaveSponsorInstructionAction::class, $save);

    // Act
    $component->call('saveInstruction');

    // Assert
    expect($received)->toBeInstanceOf(SponsorInstructionData::class)
        ->and($received?->mode)->toBe(SponsorInstructionMode::Setup)
        ->and((string) $received?->targetKey)->toBe($promise->pack_key)
        ->and($received?->reason)->toBe('Moved');
});
