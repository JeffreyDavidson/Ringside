<?php

declare(strict_types=1);

namespace App\Livewire\Roster\Availability\Components;

use App\Actions\Roster\Availability\CancelAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Enums\Roster\BookableRosterKind;
use App\Exceptions\BaseBusinessException;
use App\Lifecycle\Roster\UpcomingBookings;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Profile extends Component
{
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

    public string $startsOn = '';

    public string $endsOn = '';

    public string $reason = '';

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
        if (in_array($property, ['startsOn', 'endsOn', 'reason'], true)) {
            $this->reviewSignature = null;
            $this->savedEntryId = null;
        }
    }

    private function validateDates(): void
    {
        $this->validate([
            'startsOn' => ['required', 'date_format:Y-m-d'],
            'endsOn' => ['required', 'date_format:Y-m-d', 'after_or_equal:startsOn'],
            'reason' => ['string', 'max:255'],
        ]);
    }

    private function signature(): string
    {
        return hash('sha256', serialize([$this->startsOn, $this->endsOn, $this->reason]));
    }

    public function review(): void
    {
        Gate::authorize('update', $this->person());
        $this->validateDates();
        $this->status = '';
        $this->reviewSignature = $this->signature();
    }

    public function save(): void
    {
        $person = $this->person();
        Gate::authorize('update', $person);
        $this->validateDates();
        if ($this->reviewSignature !== $this->signature()) {
            $this->addError('startsOn', __('availability.review_first'));

            return;
        }
        try {
            $entry = resolve(CreateAction::class)->handle($person, $this->startsOn, $this->endsOn, $this->reason, $this->actor());
        } catch (BaseBusinessException $exception) {
            $this->addError('startsOn', $exception->getMessage());

            return;
        }
        $this->status = __('availability.saved');
        $this->savedEntryId = $entry->id;
        $this->reviewSignature = null;
        $this->reset('startsOn', 'endsOn', 'reason');
        $this->resetPage('availabilityPage');
    }

    public function requestCancel(int $id): void
    {
        $person = $this->person();
        Gate::authorize('update', $person);
        $person->unavailabilities()->where('promotion_id', $person->promotion_id)->active()->findOrFail($id);
        $this->cancelId = $id;
    }

    public function cancel(): void
    {
        $person = $this->person();
        Gate::authorize('update', $person);
        if ($this->cancelId === null) {
            return;
        }
        resolve(CancelAction::class)->handle($person, $this->cancelId, $this->actor());
        $this->cancelId = null;
        $this->savedEntryId = null;
        $this->status = __('availability.cancelled');
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    /** @return Collection<int, Event> */
    private function affected(Wrestler|Referee $person): Collection
    {
        $startsOn = $this->startsOn;
        $endsOn = $this->endsOn;
        if ($this->savedEntryId !== null) {
            $entry = $person->unavailabilities()->where('promotion_id', $person->promotion_id)->findOrFail($this->savedEntryId);
            $startsOn = $entry->starts_on;
            $endsOn = $entry->ends_on;
        } elseif ($this->reviewSignature !== $this->signature()) {
            return collect();
        }

        return resolve(UpcomingBookings::class)->events($person, $person->promotion_id)
            ->filter(function (Event $event) use ($startsOn, $endsOn): bool {
                $day = $event->local_date?->toDateString();

                return $day === null || ($day >= $startsOn && $day <= $endsOn);
            })
            ->values();
    }

    public function render(): View
    {
        $person = $this->person();
        Gate::authorize('view', $person);
        $person->loadMissing('promotion');
        $zone = Promotion::zoneOf($person->promotion);
        $day = now()->setTimezone($zone)->toDateString();

        return view('livewire.roster.availability.components.profile', [
            'person' => $person, 'zone' => $zone, 'affected' => $this->affected($person),
            'entries' => $person->unavailabilities()->where('promotion_id', $person->promotion_id)->active()->where('ends_on', '>=', $day)
                ->orderBy('starts_on')->orderBy('id')->paginate(10, pageName: 'availabilityPage'),
            'history' => $person->unavailabilities()->where('promotion_id', $person->promotion_id)
                ->where(fn (Builder $query): Builder => $query->whereNotNull('cancelled_at')->orWhere('ends_on', '<', $day))
                ->orderByDesc('starts_on')->orderByDesc('id')->paginate(10, pageName: 'availabilityHistoryPage'),
        ]);
    }
}
