<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\Events\TalentBookingStatus;
use App\Models\Events\Event;
use App\Queries\Events\TalentBookingContextQuery;
use App\Queries\Events\TalentBookingRosterQuery;
use App\Services\Events\TalentBookingFingerprintService;

/**
 * @phpstan-import-type PersonRow from TalentBookingRosterQuery
 *
 * @phpstan-type BookingRow array{key: string, type: string, id: int, name: string, role: string, matches: list<int>, on_card: bool, unavailable: bool, booking: ?\App\Models\Events\EventTalentBooking, warnings: list<string>}
 * @phpstan-type Screen array{rows: list<BookingRow>, counts: array<string, int>, context: array<string, mixed>, card_warning: bool}
 */
class TalentBookingsViewModel
{
    public function __construct(private readonly TalentBookingRosterQuery $roster, private readonly TalentBookingContextQuery $context, private readonly TalentBookingFingerprintService $fingerprints) {}

    public function fee(?int $minor): string
    {
        if ($minor === null) {
            return __('talent-bookings.unknown');
        }

        return number_format(intdiv($minor, 100), 0).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    /** @return Screen */
    public function data(Event $event): array
    {
        $data = $this->roster->get($event);
        $context = $this->context->get($event);
        $rows = [];
        $counts = ['not_tracked' => 0, 'proposed' => 0, 'contacted' => 0, 'confirmed' => 0, 'cancelled' => 0, 'attention' => 0, 'confirmed_clear' => 0];
        foreach ($data['rows'] as $row) {
            $booking = $row['booking'];
            $warnings = [];
            if ($booking?->status === TalentBookingStatus::Confirmed) {
                $agreement = $booking->confirmed_snapshot;
                if ($row['unavailable']) {
                    $warnings[] = 'unavailable';
                }
                if ($agreement === null || $booking->terms_need_review || $this->fingerprints->signature($context) !== $this->fingerprints->signature($agreement['context'])) {
                    $warnings[] = 'reconfirmation';
                }
                if (($agreement['on_card'] ?? false) && ! $row['on_card']) {
                    $warnings[] = 'removed';
                }
                if ($context['venue_deleted']) {
                    $warnings[] = 'venue_deleted';
                }
                if ($context['venue_id'] === null) {
                    $warnings[] = 'venue_unset';
                }
                if ($warnings === []) {
                    $counts['confirmed_clear']++;
                }
            }
            if ($row['on_card'] && $booking?->status === TalentBookingStatus::Cancelled) {
                $warnings[] = 'cancelled_card';
            }
            if ($row['on_card'] && $booking === null) {
                $warnings[] = 'not_tracked';
            }
            $counts[$booking?->status->value ?? 'not_tracked']++;
            if ($warnings !== []) {
                $counts['attention']++;
            }
            $rows[] = [...$row, 'warnings' => $warnings];
        }

        return ['rows' => $rows, 'counts' => $counts, 'context' => $context, 'card_warning' => $data['card_warning']];
    }
}
