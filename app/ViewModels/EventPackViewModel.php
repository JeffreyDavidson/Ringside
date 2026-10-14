<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Queries\Events\EventPackCardQuery;
use Illuminate\Support\Facades\Gate;

/**
 * @phpstan-import-type Content from EventPack
 * @phpstan-import-type Timing from EventPack
 * @phpstan-import-type MatchEntry from EventPack
 * @phpstan-import-type Segment from EventPack
 * @phpstan-import-type CardRow from EventPackCardQuery
 *
 * @phpstan-type RunningRow array{key: string, label: string, details: string, referees: string, warning: bool, timing: Timing|array{}}
 *
 * @phpstan-import-type Card from EventPackCardQuery
 */
class EventPackViewModel
{
    public function __construct(private readonly EventPackCardQuery $query) {}

    /** @return array{event: Event, pack: ?EventPack, card: Card, content: Content, needsReview: bool, removed: list<MatchEntry>, running: list<RunningRow>, unplaced: list<Segment>} */
    public function data(Event $event): array
    {
        $card = $this->query->get($event);
        $pack = $event->pack()->first();
        $content = $pack->content ?? ['timezone' => $card['timezone'], 'doors_at' => null, 'revision' => null, 'master' => null, 'notes' => null, 'follow_up' => null, 'matches' => [], 'segments' => []];

        if (Gate::denies('viewProduction', $event)) {
            unset($content['production']);
            if ($pack !== null) {
                $pack = clone $pack;
                $pack->content = $content;
            }
        }

        $ids = array_column($card['rows'], 'id');
        $timings = collect($content['matches'])->keyBy('match_id');
        $segments = collect($content['segments']);
        $running = [];
        foreach ($segments->whereNull('after_match_id') as $segment) {
            $running[] = ['key' => 'segment:'.$segment['id'], 'label' => $segment['label'], 'details' => '', 'referees' => '', 'warning' => false, 'timing' => $segment];
        }
        foreach ($card['rows'] as $row) {
            $running[] = ['key' => 'match:'.$row['id'], 'label' => $row['number'].'. '.($row['label'] ?: __('event-pack.no_competitors')),
                'details' => $row['details'], 'referees' => $row['referees'], 'warning' => $row['warning'], 'timing' => $timings->get($row['id'], [])];
            foreach ($segments->where('after_match_id', $row['id']) as $segment) {
                $running[] = ['key' => 'segment:'.$segment['id'], 'label' => $segment['label'], 'details' => '', 'referees' => '', 'warning' => false, 'timing' => $segment];
            }
        }

        return ['event' => $event, 'pack' => $pack, 'card' => $card, 'content' => $content,
            'removed' => array_values($timings->whereNotIn('match_id', $ids)->all()), 'running' => $running,
            'unplaced' => array_values($segments->filter(fn (array $segment): bool => $segment['after_match_id'] !== null && ! in_array($segment['after_match_id'], $ids, true))->all()),
            'needsReview' => $pack !== null && $pack->card_fingerprint !== $card['fingerprint']];
    }
}
