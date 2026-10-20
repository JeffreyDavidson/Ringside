<?php

declare(strict_types=1);

namespace App\ViewModels\Events;

use App\Enums\Events\TalentBookingStatus;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Events\EventTalentBookingRevision;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\BookableIdsQuery;
use App\Queries\Events\EventTalentBookingsQuery;
use App\Queries\Events\EventTalentPeopleQuery;
use App\Services\Events\EventPackCardService;
use App\Services\Events\TalentBookingContextService;
use App\Services\Events\TalentBookingFingerprintService;
use App\ValueObjects\UsdAmount;

/**
 * @phpstan-import-type Context from TalentBookingContextService
 *
 * @phpstan-type PersonRow array{key: string, type: string, id: int, name: string, role: string, matches: list<int>, on_card: bool, unavailable: bool, booking: ?EventTalentBooking}
 * @phpstan-type BookingRow array{key: string, type: string, id: int, name: string, role: string, matches: list<int>, on_card: bool, unavailable: bool, booking: ?\App\Models\Events\EventTalentBooking, warnings: list<string>}
 * @phpstan-type Screen array{rows: list<BookingRow>, counts: array<string, int>, context: Context, card_warning: bool, agreedFee: ?string, historyFees: array<int, string>}
 */
class TalentBookingsViewModel
{
    public function __construct(
        private readonly EventPackCardService $card,
        private readonly EventTalentBookingsQuery $bookings,
        private readonly EventTalentPeopleQuery $people,
        private readonly BookableIdsQuery $bookable,
        private readonly TalentBookingContextService $context,
        private readonly TalentBookingFingerprintService $fingerprints,
    ) {}

    /**
     * @param  ?EventTalentBooking  $selected  the booking open in the editor, whose agreed terms are labelled
     * @param  iterable<EventTalentBookingRevision>  $history  the revisions shown for it, whose fees are labelled
     * @return Screen
     */
    public function data(Event $event, ?EventTalentBooking $selected = null, iterable $history = []): array
    {
        $data = $this->roster($event);
        $context = $this->context->get($event);
        $rows = [];
        $counts = ['not_tracked' => 0, 'proposed' => 0, 'contacted' => 0, 'confirmed' => 0, 'cancelled' => 0, 'attention' => 0, 'confirmed_clear' => 0];
        foreach ($data['rows'] as $row) {
            $booking = $row['booking'];
            $warnings = $this->warnings($row, $context);
            if ($booking?->status === TalentBookingStatus::Confirmed && $warnings === []) {
                $counts['confirmed_clear']++;
            }
            $counts[$booking?->status->value ?? 'not_tracked']++;
            if ($warnings !== []) {
                $counts['attention']++;
            }
            $rows[] = [...$row, 'warnings' => $warnings];
        }

        $historyFees = [];
        foreach ($history as $revision) {
            $fee = $revision->snapshot['fee_minor'] ?? null;
            $historyFees[$revision->id] = $this->fee(is_int($fee) ? $fee : null);
        }

        return ['rows' => $rows, 'counts' => $counts, 'context' => $context, 'card_warning' => $data['card_warning'],
            'agreedFee' => $selected?->confirmed_snapshot === null ? null : $this->fee($selected->confirmed_snapshot['terms']['fee_minor']),
            'historyFees' => $historyFees];
    }

    /**
     * Everyone on the card plus everyone with a booking, with whether they are on the card and can still be booked.
     *
     * @return array{rows: list<PersonRow>, card_warning: bool}
     */
    private function roster(Event $event): array
    {
        $card = $this->card->get($event);
        $bookings = $this->bookings->get($event);
        $rows = collect($card['roster'])->keyBy('key');
        $onCard = $rows->all();
        // The first booking wins if a person were ever listed twice, as the lookup this replaces took the first match.
        $booked = $bookings->unique($this->bookingKey(...))->keyBy($this->bookingKey(...));
        foreach ($booked as $key => $booking) {
            if (! $rows->has($key)) {
                $rows->put($key, ['key' => $key, 'name' => $booking->person_name, 'role' => $booking->person_role, 'matches' => [], 'warning' => true]);
            }
        }
        $result = [];
        foreach ([(new Wrestler)->getMorphClass() => Wrestler::class, (new Referee)->getMorphClass() => Referee::class] as $type => $class) {
            $typed = $rows->filter(fn (array $row): bool => str_starts_with($row['key'], $type.':'));
            $ids = $typed->map(fn (array $row): int => (int) explode(':', $row['key'])[1])->all();
            $people = $this->people->get($class, $event->promotion_id, $ids);
            $available = $this->bookable->get($class, $ids);
            foreach ($typed as $key => $row) {
                $id = (int) explode(':', $key)[1];
                $person = $people->get($id);
                $result[] = ['key' => $key, 'type' => $type, 'id' => $id,
                    'name' => $person instanceof Wrestler ? $person->name : ($person->full_name ?? $row['name']),
                    'role' => $row['role'], 'matches' => $row['matches'], 'on_card' => array_key_exists($key, $onCard),
                    'unavailable' => $person === null || ! in_array($id, $available, true),
                    'booking' => $booked->get($key)];
            }
        }
        usort($result, fn (array $a, array $b): int => [mb_strtolower($a['name']), $a['key']] <=> [mb_strtolower($b['name']), $b['key']]);

        return ['rows' => $result, 'card_warning' => in_array(true, array_column($card['rows'], 'warning'), true)];
    }

    /** The key a booking's person has in the roster, such as `wrestler:12`. */
    private function bookingKey(EventTalentBooking $booking): string
    {
        return "{$booking->person_type}:{$booking->person_id}";
    }

    /**
     * Why a row needs attention, in the order the page lists them. A confirmed booking is checked against the
     * current event; a cancelled or missing booking only matters when the person is on the card.
     *
     * @param  PersonRow  $row
     * @param  Context  $context
     * @return list<string>
     */
    private function warnings(array $row, array $context): array
    {
        $booking = $row['booking'];
        $confirmed = $booking?->status === TalentBookingStatus::Confirmed;
        $agreement = $booking?->confirmed_snapshot;

        // Every entry is evaluated eagerly, so each one guards itself (`$confirmed &&`) instead of relying on an outer branch.
        return array_keys(array_filter([
            'unavailable' => $confirmed && $row['unavailable'],
            'reconfirmation' => $confirmed && ($agreement === null || $booking->terms_need_review
                || $this->fingerprints->signature($context) !== $this->fingerprints->signature($agreement['context'])),
            'removed' => $confirmed && ($agreement['on_card'] ?? false) && ! $row['on_card'],
            'venue_deleted' => $confirmed && $context['venue_deleted'],
            'venue_unset' => $confirmed && $context['venue_id'] === null,
            'cancelled_card' => $row['on_card'] && $booking?->status === TalentBookingStatus::Cancelled,
            'not_tracked' => $row['on_card'] && $booking === null,
        ]));
    }

    /** The fee as the page shows it: a currency prefix, thousands grouping, exact cents and a sign for negative amounts. */
    private function fee(?int $minor): string
    {
        if ($minor === null) {
            return 'USD '.__('talent-bookings.unknown');
        }
        [$whole, $cents] = explode('.', UsdAmount::decimal(abs($minor)));

        return sprintf('USD %s%s.%s', $minor < 0 ? '-' : '', number_format((int) $whole), $cents);
    }
}
