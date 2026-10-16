<?php

declare(strict_types=1);

require_once __DIR__.'/../../Fixtures/sponsor-commitment.php';

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Actions\Events\SaveSponsorInstructionAction;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Matches\EventMatch;
use App\Services\Events\SponsorInstructionService;
use App\ViewModels\EventPackViewModel;
use App\ViewModels\SponsorCommitmentsViewModel;

test('overdue completed late and unknown deadlines use recorded facts', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $screen = app(SponsorCommitmentsViewModel::class);
    $action = app(SaveSponsorCommitmentAction::class);

    // Act and Assert
    expect($screen->status($promise))->toBe('Pending');
    $promise = $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['deadline' => now()->subHour()]));
    expect($screen->status($promise))->toBe('Overdue');
    $promise = $action->handle($world['event'], $promise->id, 2, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Completed, 'evidence' => 'Banner checked']));
    expect($screen->status($promise))->toBe('Completed late');
    $promise = $action->handle($world['event'], $promise->id, 3, $promise->submission_key, sponsorPromiseData($promise, ['deadline' => null]), 'Correct deadline');
    expect($screen->status($promise))->toBe('Completed');
});

test('source review identifies event changes and removed or edited instructions', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $screen = app(SponsorCommitmentsViewModel::class);
    $promise->load(['event.promotion', 'event.pack', 'event.liveRun', 'event.matches']);

    // Act and Assert
    expect($screen->eventNeedsReview($promise))->toBeFalse()
        ->and($screen->instructionNeedsReview($promise))->toBeFalse()
        ->and($screen->liveState($promise))->toBeNull();
    $world['event']->update(['date' => now()->addDay()]);
    $promise->unsetRelation('event');
    expect($screen->eventNeedsReview($promise))->toBeTrue();
});

test('event listings filter terminal promises and use deterministic deadline order', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $later = EventSponsorCommitment::factory()->create(['event_id' => $promise->event_id, 'promotion_id' => $promise->promotion_id, 'sponsor_id' => $promise->sponsor_id, 'deadline_at' => now()->addDay()]);
    $earlier = EventSponsorCommitment::factory()->create(['event_id' => $promise->event_id, 'promotion_id' => $promise->promotion_id, 'sponsor_id' => $promise->sponsor_id, 'deadline_at' => now()->subDay()]);
    $finished = EventSponsorCommitment::factory()->create(['event_id' => $promise->event_id, 'promotion_id' => $promise->promotion_id, 'sponsor_id' => $promise->sponsor_id, 'status' => SponsorCommitmentStatus::Cancelled]);

    // Act
    $pending = app(SponsorCommitmentsViewModel::class)->rows($world['event'], 'pending');
    $cancelled = app(SponsorCommitmentsViewModel::class)->rows($world['event'], 'cancelled');
    $all = app(SponsorCommitmentsViewModel::class)->rows($world['event'], 'all');

    // Assert
    expect($pending->getCollection()->map(fn (EventSponsorCommitment $row): int => $row->id)->reject(fn (int $id): bool => $id === $promise->id)->values()->all())->toBe([$earlier->id, $later->id])
        ->and($cancelled->pluck('id')->all())->toBe([$finished->id])->and($all->total())->toBe(4);
});

test('a cancelled promise stays visible for review while its approved instruction remains in the pack', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key,
        sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']));
    $pack = app(EventPackViewModel::class)->data($world['event']);
    app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], 'setup', null, null,
        app(SponsorInstructionService::class)->preview($world['event'], $promise, 'setup', null, null)['hash']);
    $promise->refresh();

    // Act
    app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 3, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Cancelled]), 'Sponsor withdrew');
    $rows = app(SponsorCommitmentsViewModel::class)->rows($world['event'], 'pending');

    // Assert
    expect($rows->pluck('id')->all())->toBe([$promise->id]);
    expect(app(SponsorCommitmentsViewModel::class)->instructionNeedsReview($rows->getCollection()->firstOrFail()))->toBeTrue();
});

test('linked announcement review preserves its live context and flags a removed anchor', function (): void {
    // Arrange
    $world = sponsorFixture();
    $match = EventMatch::factory()->for($world['event'])->create(['match_number' => 1]);
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']));
    $pack = app(EventPackViewModel::class)->data($world['event']);
    $query = app(SponsorInstructionService::class);
    app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], 'segment', null, $match->id, $query->preview($world['event'], $promise, 'segment', null, $match->id)['hash']);
    app(PrepareLiveShowAction::class)->handle($world['event'], 0, 1, $pack['card']['fingerprint']);
    $screen = app(SponsorCommitmentsViewModel::class);
    $row = $screen->rows($world['event'], 'pending')->getCollection()->firstOrFail();

    // Act and Assert
    expect($screen->instructionNeedsReview($row))->toBeFalse()->and($screen->liveState($row))->toBe('Live snapshot: Pending');
    $match->delete();
    $row = $screen->rows($world['event'], 'pending')->getCollection()->firstOrFail();
    expect($screen->instructionNeedsReview($row))->toBeTrue();
});

test('setup review detects unpublished private edits changed pack text and removed items', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors', 'instructionOwner' => 'Ring crew']));
    $pack = app(EventPackViewModel::class)->data($world['event']);
    $query = app(SponsorInstructionService::class);
    app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], 'setup', null, null, $query->preview($world['event'], $promise, 'setup', null, null)['hash']);
    $screen = app(SponsorCommitmentsViewModel::class);
    $row = $screen->rows($world['event'], 'pending')->getCollection()->firstOrFail();

    // Act and Assert
    expect($screen->instructionNeedsReview($row))->toBeFalse();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 3, $promise->submission_key, sponsorPromiseData($promise->refresh(), ['instructionOwner' => 'Alex']));
    $row = $screen->rows($world['event'], 'pending')->getCollection()->firstOrFail();
    expect($screen->instructionNeedsReview($row))->toBeTrue();
    $data = app(EventPackViewModel::class)->data($world['event']);
    $data['content']['sponsor_tasks'] = [];
    app(SaveEventPackAction::class)->handle($world['event'], 1, $data['card']['fingerprint'], $data['content'], false);
    $row = $screen->rows($world['event'], 'pending')->getCollection()->firstOrFail();
    expect($screen->instructionNeedsReview($row))->toBeTrue();
});
