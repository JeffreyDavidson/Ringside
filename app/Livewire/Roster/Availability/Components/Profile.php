<?php

declare(strict_types=1);

namespace App\Livewire\Roster\Availability\Components;

use App\Actions\Roster\Availability\CancelAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Enums\Roster\BookableRosterKind;
use App\Exceptions\BaseBusinessException;
use App\Lifecycle\Roster\UpcomingBookings;
use App\Livewire\Concerns\ResolvesActor;
use App\Livewire\Roster\Availability\Forms\UnavailabilityForm;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Roster\CurrentUnavailabilitiesQuery;
use App\Queries\Roster\UnavailabilityHistoryQuery;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Profile extends Component
{
    use ResolvesActor;
    use WithPagination;

    #[Locked]
    public int $personId;

    #[Locked]
    public BookableRosterKind $personKind;

    #[Locked]
    public ?string $reviewSignature = null;

    #[Locked]
    public ?int $cancelId = null;

    #[Locked]
    public ?int $savedEntryId = null;

    public UnavailabilityForm $form;

    public string $status = '';

    private Wrestler|Referee|null $loadedPerson = null;

    public function mount(string $kind, int $personId): void
    {
        $this->personKind = match ($kind) {
            'wrestlers' => BookableRosterKind::Wrestlers,
            'referees' => BookableRosterKind::Referees,
            default => abort(404),
        };
        $this->personId = $personId;
        Gate::authorize('view', $this->person());
    }

    private function person(): Wrestler|Referee
    {
        return $this->loadedPerson ??= $this->personKind === BookableRosterKind::Wrestlers
            ? Wrestler::query()->findOrFail($this->personId)
            : Referee::query()->findOrFail($this->personId);
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'form.')) {
            $this->reviewSignature = null;
            $this->savedEntryId = null;
        }
    }

    public function review(): void
    {
        Gate::authorize('update', $this->person());
        $this->form->toData();
        $this->status = '';
        $this->reviewSignature = $this->form->signature();
    }

    public function save(CreateAction $create): void
    {
        $person = $this->person();
        Gate::authorize('update', $person);
        $data = $this->form->toData();

        if ($this->reviewSignature !== $this->form->signature()) {
            $this->addError('form.startsOn', __('availability.review_first'));

            return;
        }

        try {
            $entry = $create->handle($person, $data, $this->actor());
        } catch (BaseBusinessException $exception) {
            $this->addError('form.startsOn', $exception->getMessage());

            return;
        }

        $this->status = __('availability.saved');
        $this->savedEntryId = $entry->id;
        $this->reviewSignature = null;
        $this->form->reset();
        $this->resetPage('availabilityPage');
    }

    public function requestCancel(int $id): void
    {
        $person = $this->person();
        Gate::authorize('update', $person);
        $person->unavailabilities()->where('promotion_id', $person->promotion_id)->active()->findOrFail($id);
        $this->cancelId = $id;
    }

    public function cancel(CancelAction $cancel): void
    {
        $person = $this->person();
        Gate::authorize('update', $person);

        if ($this->cancelId === null) {
            return;
        }

        $cancel->handle($person, $this->cancelId, $this->actor());
        $this->cancelId = null;
        $this->savedEntryId = null;
        $this->status = __('availability.cancelled');
    }

    /** @return Collection<int, Event> */
    private function affected(UpcomingBookings $upcomingBookings, Wrestler|Referee $person): Collection
    {
        $startsOn = $this->form->startsOn;
        $endsOn = $this->form->endsOn;

        if ($this->savedEntryId !== null) {
            $entry = $person->unavailabilities()->where('promotion_id', $person->promotion_id)->findOrFail($this->savedEntryId);
            $startsOn = $entry->starts_on;
            $endsOn = $entry->ends_on;
        } elseif ($this->reviewSignature !== $this->form->signature()) {
            return collect();
        }

        return $upcomingBookings->events($person, $person->promotion_id)
            ->filter(function (Event $event) use ($startsOn, $endsOn): bool {
                $day = $event->local_date?->toDateString();

                return $day === null || ($day >= $startsOn && $day <= $endsOn);
            })
            ->values();
    }

    public function render(UpcomingBookings $upcomingBookings, CurrentUnavailabilitiesQuery $currentEntries, UnavailabilityHistoryQuery $historyEntries): View
    {
        $person = $this->person();
        Gate::authorize('view', $person);
        $person->loadMissing('promotion');
        $zone = PromotionTimeZoneService::zoneOf($person->promotion);
        $day = now()->setTimezone($zone)->toDateString();

        return view('livewire.roster.availability.components.profile', [
            'person' => $person, 'zone' => $zone, 'affected' => $this->affected($upcomingBookings, $person),
            'entries' => $currentEntries->get($person, $day)->paginate(10, pageName: 'availabilityPage'),
            'history' => $historyEntries->get($person, $day)->paginate(10, pageName: 'availabilityHistoryPage'),
        ]);
    }
}
