<?php

declare(strict_types=1);
use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentMatchLink;

it('keeps tournament link and entrant factories inside their owning promotion', function (): void {
    $link = TournamentMatchLink::factory()->create();
    $entry = TournamentEntry::factory()->for($link->tournament)->create();
    expect($entry->tournament->is($link->tournament))->toBeTrue()->and($entry->competitor->promotion_id)->toBe($link->promotion_id)
        ->and($link->node->promotion_id)->toBe($link->promotion_id)->and($link->match->event->promotion_id)->toBe($link->promotion_id);
});
