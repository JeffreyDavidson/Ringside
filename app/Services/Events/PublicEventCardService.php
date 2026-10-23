<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\Events\PublicAnnouncement;
use App\Models\Events\EventPublication;
use Illuminate\Support\Carbon;

/**
 * Turns the public event source into what is released: the card, its rows, and the fingerprints a snapshot is
 * compared against. Fingerprints are stored with released snapshots, so what goes into them must not change.
 *
 * @phpstan-import-type Draft from EventPublication
 * @phpstan-import-type Selection from EventPublication
 * @phpstan-import-type Card from EventPublication
 * @phpstan-import-type Row from EventPublication
 * @phpstan-import-type Source from PublicEventSourceService
 * @phpstan-import-type MatchRow from PublicEventSourceService
 */
class PublicEventCardService
{
    /** @param  array<array-key, mixed>  $values */
    public function fingerprint(array $values): string
    {
        return hash('sha256', serialize($values));
    }

    /**
     * @param  Source  $source
     * @param  list<Selection>  $selection
     * @return list<Row>
     */
    public function rows(array $source, array $selection): array
    {
        $selected = collect($selection)->keyBy('id');
        $rows = [];

        foreach ($source['matches'] as $match) {
            $choice = $selected->get($match['id']);

            if ($choice === null) {
                continue;
            }
            $rows[] = ['label' => $match['label'], 'details' => implode(' · ', array_filter([
                $choice['type'] ? $match['type'] : null, $choice['stipulation'] ? $match['stipulation'] : null,
                $choice['titles'] ? $match['titles'] : null])), 'outcome' => null];
        }

        return $rows;
    }

    /**
     * @param  Source  $source
     * @param  list<Selection>  $selection
     */
    public function cardFingerprint(array $source, array $selection): string
    {
        return $this->fingerprint([$source['title'], $source['promotion'], $this->rows($source, $selection)]);
    }

    /**
     * @param  Source  $source
     * @param  list<int>  $ids
     * @return list<MatchRow>
     */
    public function resultSource(array $source, array $ids): array
    {
        return array_values(array_filter($source['matches'], fn (array $match): bool => in_array($match['id'], $ids, true)));
    }

    /**
     * @param  Source  $source
     * @param  Draft  $draft
     * @return Card
     */
    public function card(array $source, array $draft): array
    {
        $notice = PublicAnnouncement::from($draft['announcement'])->isNotice();

        return ['title' => $source['title'], 'promotion' => $source['promotion'], 'announcement' => $draft['announcement'],
            'summary' => $draft['summary'], 'notice' => $draft['notice'], 'date' => $notice ? null : $source['date'],
            'timezone' => $source['timezone'], 'venue' => $source['venue'], 'address' => $draft['address'] ? $source['address'] : null,
            'doors' => $notice || $draft['doors_at'] === null ? null : Carbon::parse($draft['doors_at'].$draft['doors_offset'])->toIso8601String(),
            'tickets' => $notice ? null : $draft['tickets'], 'poster' => $draft['poster'], 'rows' => $notice ? [] : $this->rows($source, $draft['matches'])];
    }
}
