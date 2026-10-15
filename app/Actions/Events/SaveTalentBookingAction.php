<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\TalentBookingData;
use App\Enums\Events\TalentBookingStatus;
use App\Exceptions\Events\TalentBookingException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Models\Events\EventTalentBookingRevision;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Queries\Events\TalentBookingContextQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** @phpstan-import-type Terms from EventTalentBooking */
class SaveTalentBookingAction
{
    public function __construct(private readonly TalentBookingContextQuery $context) {}

    public function handle(Event $event, string $personType, int $personId, int $version, string $fingerprint, TalentBookingData $data): EventTalentBooking
    {
        return DB::transaction(function () use ($event, $personType, $personId, $version, $fingerprint, $data): EventTalentBooking {
            // Match edits and event rescheduling also lock the event before touching child records.
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $event);
            $booking = $event->talentBookings()->where('person_type', $personType)->where('person_id', $personId)->lockForUpdate()->first();
            // Keep timezone and location stable while checking and recording the agreed context.
            $event->promotion()->lockForUpdate()->first();
            $event->venue()->withTrashed()->lockForUpdate()->first();
            $person = match ($personType) {
                (new Wrestler)->getMorphClass() => Wrestler::query()->withTrashed()->where('promotion_id', $event->promotion_id)->find($personId),
                (new Referee)->getMorphClass() => Referee::query()->withTrashed()->where('promotion_id', $event->promotion_id)->find($personId),
                default => throw TalentBookingException::because('person'),
            };
            if ($person === null && $booking === null) {
                throw TalentBookingException::because('person');
            }
            $key = $personType.':'.$personId;
            if (($booking->version ?? 0) !== $version || ! hash_equals($this->context->fingerprint($event, $key), $fingerprint)) {
                throw RecordChangedException::changed();
            }
            if ($booking?->status === TalentBookingStatus::Cancelled && $data->status !== TalentBookingStatus::Proposed) {
                throw TalentBookingException::because('reopen');
            }
            if (($data->status === TalentBookingStatus::Cancelled || ($booking?->status === TalentBookingStatus::Confirmed
                && in_array($data->status, [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted], true))) && blank($data->reason)) {
                throw TalentBookingException::because('reason');
            }
            if ($data->status === TalentBookingStatus::Confirmed && ($event->date === null || ($booking?->status !== TalentBookingStatus::Confirmed && ! $data->attest))) {
                throw TalentBookingException::because('confirm');
            }
            if ($data->feeMinor !== null && $data->feeMinor < 0) {
                throw TalentBookingException::because('fee');
            }
            $booking ??= new EventTalentBooking;
            $previousTerms = $this->terms($booking);
            $terms = $data->status === TalentBookingStatus::Cancelled || $booking->status === TalentBookingStatus::Cancelled
                ? $previousTerms : ['fee_minor' => $data->feeMinor, 'arrival_at' => $data->arrival?->copy()->utc()->toIso8601String(),
                    'arrival_timezone' => $data->arrivalTimezone, 'travel_notes' => $data->travelNotes];
            $booking->event_id = $event->id;
            $booking->person_type = $personType;
            $booking->person_id = $personId;
            $booking->person_name = $person instanceof Wrestler ? $person->name : ($person->full_name ?? $booking->person_name);
            $booking->person_role = $personType === (new Wrestler)->getMorphClass() ? __('event-pack.wrestler') : __('event-pack.referee');
            $booking->status = $data->status;
            $booking->fee_minor = $terms['fee_minor'];
            $booking->currency = 'USD';
            $booking->arrival_at = $terms['arrival_at'] === null ? null : Carbon::parse($terms['arrival_at']);
            $booking->arrival_timezone = $terms['arrival_timezone'];
            $booking->travel_notes = $terms['travel_notes'];
            $booking->terms_need_review = $booking->terms_need_review || ($data->status === TalentBookingStatus::Confirmed && $previousTerms !== $terms);
            if ($data->status === TalentBookingStatus::Confirmed && $data->attest) {
                $booking->confirmed_snapshot = ['context' => $this->context->get($event), 'on_card' => $this->context->onCard($event, $key),
                    'terms' => $terms, 'confirmed_at' => now()->utc()->toIso8601String()];
                $booking->terms_need_review = false;
            }
            if ($booking->exists && ! $booking->isDirty()) {
                return $booking;
            }
            $booking->version = $version + 1;
            $booking->save();
            /** @var User $actor */
            $actor = auth()->user();
            $revision = new EventTalentBookingRevision;
            $revision->booking_id = $booking->id;
            $revision->version = $booking->version;
            $revision->operation = $data->attest && $data->status === TalentBookingStatus::Confirmed ? 'agreement' : $data->status->value;
            $revision->reason = $data->reason;
            $revision->snapshot = [...$terms, 'status' => $booking->status->value, 'currency' => 'USD',
                'person_type' => $personType, 'person_id' => $personId, 'person_name' => $booking->person_name, 'person_role' => $booking->person_role,
                'context' => $this->context->get($event), 'confirmed_snapshot' => $booking->confirmed_snapshot, 'terms_need_review' => $booking->terms_need_review];
            $revision->actor_id = $actor->id;
            $revision->actor_name = User::query()->findOrFail($actor->id)->full_name;
            $revision->save();

            return $booking;
        }, attempts: 3);
    }

    /** @return Terms */
    private function terms(EventTalentBooking $booking): array
    {
        return ['fee_minor' => $booking->fee_minor, 'arrival_at' => $booking->arrival_at?->utc()->toIso8601String(),
            'arrival_timezone' => $booking->arrival_timezone ?? 'UTC', 'travel_notes' => $booking->travel_notes];
    }
}
