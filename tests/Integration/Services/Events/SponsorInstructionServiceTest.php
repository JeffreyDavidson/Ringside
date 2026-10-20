<?php

declare(strict_types=1);

use App\Enums\Events\SponsorInstructionMode;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Services\Events\SponsorInstructionService;

test('a preview hash stays byte-identical for each mode so stored review hashes keep matching', function (string $mode, string $hash): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $commitment = EventSponsorCommitment::factory()
        ->make([
            'version' => 2,
            'instruction_label' => 'Entrance banner',
            'instruction' => 'Hang before doors',
            'instruction_owner' => 'Ring crew',
        ]);

    // Act
    $preview = resolve(SponsorInstructionService::class)->preview($event, $commitment, SponsorInstructionMode::from($mode), null, null);

    // Assert
    expect($preview['hash'])->toBe($hash);
})->with([
    'segment' => ['segment', 'c70d706bba64bb302f78270a6dff944a72d0f29eaf061176343a0ef863096909'],
    'setup' => ['setup', '5afdbfaf58b72e27a377976dcd0e3f3f32dd80a7d429ef3777fc31de7561220a'],
    'remove' => ['remove', '56ba8d83f63172ea0b9dc76634bb93b1b6b1851b2a052bfa37da68fc99e3f7c1'],
]);
