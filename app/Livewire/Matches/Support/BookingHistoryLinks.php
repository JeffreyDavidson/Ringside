<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ViewModels\Matches\BookingHistoryViewModel;

/**
 * Adds the page links to a booking history snapshot: profiles of competitors that still exist, the match history
 * filtered to an opponent, and the events the matches came from.
 *
 * @phpstan-import-type Snapshot from BookingHistoryViewModel
 * @phpstan-import-type Pair from BookingHistoryViewModel
 *
 * @phpstan-type LinkedSnapshot array{available: bool, reference: string, from: string, subjects: list<array<string, mixed>>, direct: array<string, mixed>|null, notices: list<array<string, mixed>>, totalNotices: int, page: int, pages: int}
 */
final class BookingHistoryLinks
{
    /**
     * @param  Snapshot  $snapshot
     * @return LinkedSnapshot
     */
    public function add(array $snapshot): array
    {
        return [
            ...$snapshot,
            'subjects' => array_map(fn (array $subject): array => [
                ...$subject,
                'url' => $subject['deleted'] ? null : $this->profile($subject['type'], $subject['id']),
                'recent' => array_map(fn (array $row): array => [...$row, 'url' => route('events.show', $row['eventId'])], $subject['recent']),
            ], $snapshot['subjects']),
            'direct' => $snapshot['direct'] === null ? null : [...$snapshot['direct'], 'url' => $this->opponent($snapshot['direct']['pair'])],
            'notices' => array_map(fn (array $notice): array => [
                ...$notice,
                'url' => $this->opponent($notice['pair']),
                'sourceUrl' => route('events.show', $notice['eventId']),
            ], $snapshot['notices']),
        ];
    }

    private function profile(string $type, int $id): string
    {
        return route($type === (new Wrestler)->getMorphClass() ? 'wrestlers.show' : 'tag-teams.show', $id);
    }

    /**
     * The profile of the competitor still on file, filtered to the other; none when both are deleted.
     *
     * @param  array{first: Pair, second: Pair}  $pair
     */
    private function opponent(array $pair): ?string
    {
        ['first' => $first, 'second' => $second] = $pair;

        if ($first['deleted'] && $second['deleted']) {
            return null;
        }

        [$profile, $opponent] = $first['deleted'] ? [$second, $first] : [$first, $second];

        return route($profile['type'] === (new TagTeam)->getMorphClass() ? 'tag-teams.show' : 'wrestlers.show', [$profile['id'], 'opponent' => "{$opponent['type']}:{$opponent['id']}"]);
    }
}
