<?php

declare(strict_types=1);

require_once __DIR__.'/../../Fixtures/live-show.php';

use App\Actions\Events\SaveEventPackAction;
use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\ViewModels\EventPackViewModel;
use App\ViewModels\EventProductionViewModel;

/** @return int how many statements read the event's saved pack */
function packReads(Closure $callback): int
{
    return collect(recordStatements($callback))
        ->filter(fn (array $statement): bool => str_contains($statement['sql'], 'from "event_packs"'))
        ->count();
}

test('the live sheet builds the pack source once', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $viewModel = resolve(EventProductionViewModel::class);

    // Act
    $reads = packReads(fn () => $viewModel->data($fixture['event'], 'live'));

    // Assert
    expect($reads)->toBe(1);
});

test('the plan sheet builds the pack source once', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $viewModel = resolve(EventProductionViewModel::class);

    // Act
    $reads = packReads(fn () => $viewModel->data($fixture['event'], 'plan'));

    // Assert
    expect($reads)->toBe(1);
});

test('the live sheet asks for review only once the saved pack has moved on from the live run', function (): void {
    // Arrange
    $fixture = liveShowFixture();
    $viewModel = resolve(EventProductionViewModel::class);
    $before = $viewModel->data($fixture['event'], 'live')['needsReview'];
    $pack = resolve(EventPackViewModel::class)->data($fixture['event']);
    resolve(SaveEventPackAction::class)->handle($fixture['event'], $pack['pack']->version ?? 0, $pack['card']['fingerprint'], $pack['content'], false);

    // Act
    $after = $viewModel->data($fixture['event'], 'live')['needsReview'];

    // Assert
    expect($before)->toBeFalse()
        ->and($after)->toBeTrue();
});

test('the sheet leaves the permission check to its caller', function (): void {
    // Arrange
    require_once __DIR__.'/../../Fixtures/production.php';
    $event = productionFixture()['event'];
    actingAsPromotionMember(Promotion::query()->findOrFail($event->promotion_id), MembershipRole::Member);

    // Act
    $sheet = resolve(EventProductionViewModel::class)->data($event, 'plan');

    // Assert
    expect($sheet['source'])->toBe('plan')
        ->and($sheet['allAssignments'])->toHaveCount(1);
});
