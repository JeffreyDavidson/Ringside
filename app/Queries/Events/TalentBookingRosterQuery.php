<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\EventPackCardService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-type PersonRow array{key: string, type: string, id: int, name: string, role: string, matches: list<int>, on_card: bool, unavailable: bool, booking: ?EventTalentBooking}
 * @phpstan-type Roster array{rows: list<PersonRow>, card_warning: bool}
 */
class TalentBookingRosterQuery
{
    public function __construct(private readonly EventPackCardService $card) {}

    /** @return Roster */
    public function get(Event $event): array
    {
        $card = $this->card->get($event);
        $bookings = $event->talentBookings()->orderBy('id')->get();
        $rows = collect($card['roster'])->keyBy('key');
        foreach ($bookings as $booking) {
            $key = $booking->person_type.':'.$booking->person_id;
            if (! $rows->has($key)) {
                $rows->put($key, ['key' => $key, 'name' => $booking->person_name, 'role' => $booking->person_role, 'matches' => [], 'warning' => true]);
            }
        }
        $result = [];
        foreach ([(new Wrestler)->getMorphClass() => Wrestler::class, (new Referee)->getMorphClass() => Referee::class] as $type => $class) {
            $typed = $rows->filter(fn (array $row): bool => str_starts_with($row['key'], $type.':'));
            $ids = $typed->map(fn (array $row): int => (int) explode(':', $row['key'])[1])->all();
            /** @var Collection<int, Wrestler|Referee> $people */
            $people = $class::query()->withTrashed()->where('promotion_id', $event->promotion_id)->whereKey($ids)->get()->keyBy('id');
            $available = $class::query()->bookable()->where('promotion_id', $event->promotion_id)->whereKey($ids)->pluck('id')->all();
            foreach ($typed as $key => $row) {
                $id = (int) explode(':', $key)[1];
                $person = $people->get($id);
                $result[] = ['key' => $key, 'type' => $type, 'id' => $id,
                    'name' => $person instanceof Wrestler ? $person->name : ($person->full_name ?? $row['name']),
                    'role' => $row['role'], 'matches' => $row['matches'], 'on_card' => in_array($key, array_column($card['roster'], 'key'), true),
                    'unavailable' => ! in_array($id, $available, true),
                    'booking' => $bookings->first(fn (EventTalentBooking $booking): bool => $booking->person_type === $type && $booking->person_id === $id)];
            }
        }
        usort($result, fn (array $a, array $b): int => [mb_strtolower($a['name']), $a['key']] <=> [mb_strtolower($b['name']), $b['key']]);

        return ['rows' => $result, 'card_warning' => in_array(true, array_column($card['rows'], 'warning'), true)];
    }
}
