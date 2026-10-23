<?php

declare(strict_types=1);

namespace App\Livewire\Events\Forms;

use App\Data\Events\TalentBookingData;
use App\Enums\Events\TalentBookingStatus;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Rules\Events\LocalTimeExists;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TalentBookingForm extends Form
{
    public string $status = 'proposed';

    public string $fee = '';

    public string $arrival = '';

    public string $travelNotes = '';

    public string $reason = '';

    public function fillBooking(?EventTalentBooking $booking, Event $event): void
    {
        $this->reset();
        $this->status = $booking?->status === TalentBookingStatus::Cancelled ? TalentBookingStatus::Proposed->value : ($booking?->status->value ?? 'proposed');
        $this->fee = $booking?->fee_minor === null ? '' : intdiv($booking->fee_minor, 100).'.'.str_pad((string) ($booking->fee_minor % 100), 2, '0', STR_PAD_LEFT);
        $this->arrival = $booking?->arrival_at?->copy()->setTimezone(PromotionTimeZoneService::zoneOf($event->promotion))->format('Y-m-d\\TH:i') ?? '';
        $this->travelNotes = $booking->travel_notes ?? '';
    }

    public function toData(Event $event, ?EventTalentBooking $booking, bool $attest): TalentBookingData
    {
        Validator::make(['form' => $this->all()], [
            'form.status' => ['required', Rule::enum(TalentBookingStatus::class)],
            'form.fee' => ['nullable', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'],
            'form.arrival' => ['bail', 'nullable', 'date_format:Y-m-d\\TH:i', new LocalTimeExists($event->promotion)],
            'form.travelNotes' => ['nullable', 'string', 'max:2000'],
            'form.reason' => ['nullable', 'string', 'max:500'],
        ], ['form.fee.regex' => __('talent-bookings.errors.fee')])->validate();
        $fee = null;

        if ($this->fee !== '') {
            $parts = explode('.', $this->fee);
            $fee = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
        }

        $arrival = $this->arrival === '' ? null : PromotionTimeZoneService::parseLocalTime($event->promotion, $this->arrival);

        // Keep the stored instant in the repeated hour, just as the event date form does.
        if ($booking?->arrival_at !== null && $this->arrival === $booking->arrival_at->copy()->setTimezone(PromotionTimeZoneService::zoneOf($event->promotion))->format('Y-m-d\\TH:i')) {
            $arrival = $booking->arrival_at;
        }

        return new TalentBookingData(
            TalentBookingStatus::from($this->status),
            $fee,
            $arrival,
            PromotionTimeZoneService::zoneOf($event->promotion),
            filled($this->travelNotes) ? $this->travelNotes : null,
            $attest,
            filled($this->reason) ? $this->reason : null,
        );
    }
}
