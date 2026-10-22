<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/sponsor-commitment.php';

use App\Actions\Events\PrepareLiveShowAction;
use App\Actions\Events\SaveEventPackAction;
use App\Actions\Events\SaveSponsorCommitmentAction;
use App\Actions\Events\SaveSponsorInstructionAction;
use App\Data\Events\EventPackData;
use App\Data\Events\SponsorInstructionData;
use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorInstructionMode;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Services\Events\SponsorInstructionService;
use App\ViewModels\Events\EventPackViewModel;
use App\ViewModels\Events\SponsorCommitmentsViewModel;

test('overdue completed late and unknown deadlines use recorded facts', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $screen = app(SponsorCommitmentsViewModel::class);
    $action = app(SaveSponsorCommitmentAction::class);

    // Act and Assert
    expect($screen->statuses([$promise])[$promise->id])->toBe('Pending');
    $promise = $action->handle($world['event'], $promise->id, 1, $promise->submission_key, sponsorPromiseData($promise, ['deadline' => now()->subHour()]), signedInUser());
    expect($screen->statuses([$promise])[$promise->id])->toBe('Overdue');
    $promise = $action->handle($world['event'], $promise->id, 2, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Completed, 'evidence' => 'Banner checked']), signedInUser());
    expect($screen->statuses([$promise])[$promise->id])->toBe('Completed late');
    $promise = $action->handle($world['event'], $promise->id, 3, $promise->submission_key, sponsorPromiseData($promise, ['deadline' => null]), signedInUser(), 'Correct deadline');
    expect($screen->statuses([$promise])[$promise->id])->toBe('Completed');
});

test('only a pending promise past its deadline is flagged overdue', function (SponsorCommitmentStatus $status, ?int $deadlineOffsetHours, bool $overdue, string $shown): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $promise->forceFill([
        'status' => $status,
        'deadline_at' => $deadlineOffsetHours === null ? null : now()->addHours($deadlineOffsetHours),
        'completed_at' => $status === SponsorCommitmentStatus::Completed ? now()->subDays(2) : null,
    ])->save();
    $promise->load(['event.promotion', 'event.pack', 'event.liveRun', 'event.matches']);

    // Act
    $detail = app(SponsorCommitmentsViewModel::class)->details([$promise])[$promise->id];

    // Assert
    expect($detail['overdue'])->toBe($overdue)
        ->and($detail['status'])->toBe($shown);
})->with([
    'pending past its deadline' => [SponsorCommitmentStatus::Pending, -1, true, 'Overdue'],
    'pending before its deadline' => [SponsorCommitmentStatus::Pending, 1, false, 'Pending'],
    'pending with no deadline' => [SponsorCommitmentStatus::Pending, null, false, 'Pending'],
    'cancelled past its deadline' => [SponsorCommitmentStatus::Cancelled, -1, false, 'Cancelled'],
    'completed after its deadline' => [SponsorCommitmentStatus::Completed, -72, false, 'Completed late'],
    'completed before its deadline' => [SponsorCommitmentStatus::Completed, 24, false, 'Completed'],
]);

test('source review identifies event changes and removed or edited instructions', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $screen = app(SponsorCommitmentsViewModel::class);
    $promise->load(['event.promotion', 'event.pack', 'event.liveRun', 'event.matches']);

    // Act and Assert
    expect($screen->details([$promise])[$promise->id]['eventNeedsReview'])->toBeFalse()
        ->and($screen->details([$promise])[$promise->id]['instructionNeedsReview'])->toBeFalse()
        ->and($screen->details([$promise])[$promise->id]['liveState'])->toBeNull();
    $world['event']->update(['date' => now()->addDay()]);
    $promise->unsetRelation('event');
    expect($screen->details([$promise])[$promise->id]['eventNeedsReview'])->toBeTrue();
});

test('event listings filter terminal promises and use deterministic deadline order', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $later = EventSponsorCommitment::factory()->create(['event_id' => $promise->event_id, 'promotion_id' => $promise->promotion_id, 'sponsor_id' => $promise->sponsor_id, 'deadline_at' => now()->addDay()]);
    $earlier = EventSponsorCommitment::factory()->create(['event_id' => $promise->event_id, 'promotion_id' => $promise->promotion_id, 'sponsor_id' => $promise->sponsor_id, 'deadline_at' => now()->subDay()]);
    $finished = EventSponsorCommitment::factory()->create(['event_id' => $promise->event_id, 'promotion_id' => $promise->promotion_id, 'sponsor_id' => $promise->sponsor_id, 'status' => SponsorCommitmentStatus::Cancelled]);

    // Act
    $pending = app(SponsorCommitmentsViewModel::class)->data($world['event'], 'pending')['rows'];
    $cancelled = app(SponsorCommitmentsViewModel::class)->data($world['event'], 'cancelled')['rows'];
    $all = app(SponsorCommitmentsViewModel::class)->data($world['event'], 'all')['rows'];

    // Assert
    expect($pending->getCollection()->map(fn (EventSponsorCommitment $row): int => $row->id)->reject(fn (int $id): bool => $id === $promise->id)->values()->all())->toBe([$earlier->id, $later->id])
        ->and($cancelled->pluck('id')->all())->toBe([$finished->id])->and($all->total())->toBe(4);
});

test('a cancelled promise stays visible for review while its approved instruction remains in the pack', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key,
        sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang at entrance']), signedInUser());
    $pack = app(EventPackViewModel::class)->data($world['event']);
    app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, app(SponsorInstructionService::class)->preview($world['event'], $promise, SponsorInstructionMode::Setup, null, null)['hash']), signedInUser());
    $promise->refresh();

    // Act
    app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 3, $promise->submission_key, sponsorPromiseData($promise, ['status' => SponsorCommitmentStatus::Cancelled]), signedInUser(), 'Sponsor withdrew');
    $rows = app(SponsorCommitmentsViewModel::class)->data($world['event'], 'pending')['rows'];

    // Assert
    expect($rows->pluck('id')->all())->toBe([$promise->id]);
    expect(app(SponsorCommitmentsViewModel::class)->data($world['event'], 'pending')['details'][$promise->id]['instructionNeedsReview'])->toBeTrue();
});

test('linked announcement review preserves its live context and flags a removed anchor', function (): void {
    // Arrange
    $world = sponsorFixture();
    $match = EventMatch::factory()->for($world['event'])->create(['match_number' => 1]);
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Sponsor thanks', 'instruction' => 'Thank Harbor Hardware']), signedInUser());
    $pack = app(EventPackViewModel::class)->data($world['event']);
    $query = app(SponsorInstructionService::class);
    app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Segment, null, $match->id, $query->preview($world['event'], $promise, SponsorInstructionMode::Segment, null, $match->id)['hash']), signedInUser());
    app(PrepareLiveShowAction::class)->handle($world['event'], 0, 1, $pack['card']['fingerprint'], administrator());
    $screen = app(SponsorCommitmentsViewModel::class);
    $row = $screen->data($world['event'], 'pending')['rows']->getCollection()->firstOrFail();

    // Act and Assert
    expect($screen->details([$row])[$row->id]['instructionNeedsReview'])->toBeFalse()->and($screen->details([$row])[$row->id]['liveState'])->toBe('Live snapshot: Pending');
    $match->delete();
    $row = $screen->data($world['event'], 'pending')['rows']->getCollection()->firstOrFail();
    expect($screen->details([$row])[$row->id]['instructionNeedsReview'])->toBeTrue();
});

test('setup review detects unpublished private edits changed pack text and removed items', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $world['promise']->id, 1, $world['promise']->submission_key, sponsorPromiseData($world['promise'], ['instructionLabel' => 'Banner', 'instruction' => 'Hang before doors', 'instructionOwner' => 'Ring crew']), signedInUser());
    $pack = app(EventPackViewModel::class)->data($world['event']);
    $query = app(SponsorInstructionService::class);
    app(SaveSponsorInstructionAction::class)->handle($world['event'], $promise->id, 2, 0, $pack['card']['fingerprint'], new SponsorInstructionData(SponsorInstructionMode::Setup, null, null, $query->preview($world['event'], $promise, SponsorInstructionMode::Setup, null, null)['hash']), signedInUser());
    $screen = app(SponsorCommitmentsViewModel::class);
    $row = $screen->data($world['event'], 'pending')['rows']->getCollection()->firstOrFail();

    // Act and Assert
    expect($screen->details([$row])[$row->id]['instructionNeedsReview'])->toBeFalse();
    $promise = app(SaveSponsorCommitmentAction::class)->handle($world['event'], $promise->id, 3, $promise->submission_key, sponsorPromiseData($promise->refresh(), ['instructionOwner' => 'Alex']), signedInUser());
    $row = $screen->data($world['event'], 'pending')['rows']->getCollection()->firstOrFail();
    expect($screen->details([$row])[$row->id]['instructionNeedsReview'])->toBeTrue();
    $data = app(EventPackViewModel::class)->data($world['event']);
    $data['content']['sponsor_tasks'] = [];
    app(SaveEventPackAction::class)->handle($world['event'], 1, $data['card']['fingerprint'], EventPackData::fromValidated($data['content']), false);
    $row = $screen->data($world['event'], 'pending')['rows']->getCollection()->firstOrFail();
    expect($screen->details([$row])[$row->id]['instructionNeedsReview'])->toBeTrue();
});

test('the promise list is built for whoever the caller lets through', function (): void {
    // Arrange
    $world = sponsorFixture();
    actingAsPromotionMember(Promotion::query()->findOrFail($world['event']->promotion_id), MembershipRole::Member);

    // Act
    $screen = app(SponsorCommitmentsViewModel::class)->data($world['event'], 'pending');

    // Assert
    expect($screen['rows']->pluck('id')->all())->toBe([$world['promise']->id])
        ->and($screen['details'][$world['promise']->id]['status'])->toBe('Pending');
});
