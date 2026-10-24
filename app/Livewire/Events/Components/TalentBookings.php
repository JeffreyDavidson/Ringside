<?php

declare(strict_types=1);

namespace App\Livewire\Events\Components;

use App\Actions\Events\SaveTalentBookingAction;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Concerns\ResolvesActor;
use App\Livewire\Events\Forms\TalentBookingForm;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\TalentBookingFingerprintService;
use App\Support\Search\LikeTerm;
use App\ViewModels\Events\TalentBookingsViewModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;

class TalentBookings extends Component
{
    use ExplainsKeptInput;
    use ResolvesActor;
    use WithPagination;

    #[Locked]
    public int $eventId;

    #[Locked]
    public string $personType = '';

    #[Locked]
    public int $personId = 0;

    #[Locked]
    public int $version = 0;

    #[Locked]
    public string $fingerprint = '';

    #[Locked]
    public string $personName = '';

    #[Locked]
    public bool $historyOpen = false;

    public TalentBookingForm $form;

    public string $filter = 'all';

    public string $searchKind = 'wrestlers';

    public ?int $selectedPerson = null;

    public string $message = '';

    protected TalentBookingFingerprintService $fingerprints;

    public function boot(TalentBookingFingerprintService $fingerprints): void
    {
        $this->fingerprints = $fingerprints;
    }

    public function mount(int $eventId): void
    {
        $this->eventId = $eventId;
        Gate::authorize('update', $this->event());
    }

    public function refreshState(): void
    {
        Gate::authorize('update', $this->event());
    }

    public function edit(string $type, int $id): void
    {
        $event = $this->event();
        Gate::authorize('update', $event);
        $booking = $event->talentBookings()->where('person_type', $type)->where('person_id', $id)->first();
        $person = match ($type) {
            (new Wrestler)->getMorphClass() => Wrestler::query()->withTrashed()->where('promotion_id', $event->promotion_id)->find($id),
            (new Referee)->getMorphClass() => Referee::query()->withTrashed()->where('promotion_id', $event->promotion_id)->find($id),
            default => null,
        };
        abort_if($person === null && $booking === null, 404);
        $this->personType = $type;
        $this->personId = $id;
        $this->personName = $person instanceof Wrestler ? $person->name : ($person->full_name ?? $booking->person_name ?? __('talent-bookings.unknown'));
        $this->version = $booking->version ?? 0;
        $this->fingerprint = $this->fingerprints->fingerprint($event, $type.':'.$id);
        $this->historyOpen = false;
        $this->resetPage('historyPage');
        $this->message = '';
        $this->resetErrorBag();
        $this->form->fillBooking($booking, $event);
        $this->dispatch('talent-editor-open');
    }

    public function updatedSearchKind(): void
    {
        Gate::authorize('update', $this->event());
        $this->selectedPerson = null;
        $this->searchKind = in_array($this->searchKind, ['wrestlers', 'referees'], true) ? $this->searchKind : 'wrestlers';
    }

    public function addPerson(): void
    {
        Gate::authorize('update', $this->event());
        $this->edit($this->searchKind === 'referees' ? (new Referee)->getMorphClass() : (new Wrestler)->getMorphClass(), $this->selectedPerson ?? 0);
    }

    public function save(SaveTalentBookingAction $action, bool $attest = false): void
    {
        $event = $this->event();
        Gate::authorize('update', $event);
        abort_if($this->personId === 0, 404);
        $booking = $event->talentBookings()->where('person_type', $this->personType)->where('person_id', $this->personId)->first();
        $data = $this->form->toData($event, $booking, $attest);
        $this->resetErrorBag();

        try {
            $action->handle($event, $this->personType, $this->personId, $this->version, $this->fingerprint, $data, $this->actor());
            $this->personId = 0;
            $this->form->reset();
            $this->message = __('talent-bookings.saved');
            $this->dispatch('talent-editor-closed');
        } catch (BaseBusinessException $exception) {
            $this->addError('booking', $this->failureMessageKeepingInput($exception));
        }
    }

    public function closeEditor(): void
    {
        Gate::authorize('update', $this->event());
        $this->personId = 0;
        $this->dispatch('talent-editor-closed');
        $this->resetErrorBag();
        $this->form->reset();
    }

    public function showHistory(string $type, int $id): void
    {
        $this->edit($type, $id);
        $this->historyOpen = true;
    }

    /** @return array<int, array{id: int, name: string}> */
    #[Renderless]
    public function searchRoster(string $kind, string $term): array
    {
        $event = $this->event();
        Gate::authorize('update', $event);
        $query = match ($kind) {
            'wrestlers' => Wrestler::query(),
            'referees' => Referee::query(),
            default => null,
        };

        if ($query === null) {
            return [];
        }

        $column = $kind === 'referees' ? 'full_name' : 'name';
        $like = LikeTerm::from($term, 100);

        /** @var Collection<int, Wrestler|Referee> $people */
        $people = $query->where('promotion_id', $event->promotion_id)
            ->when(! $like->isEmpty(), fn (Builder $query): Builder => $query->whereLike($column, $like->contains(), caseSensitive: false))
            ->orderBy($column)->orderBy('id')->limit(20)->get();

        return $people->map(fn (Wrestler|Referee $person): array => ['id' => $person->id,
            'name' => $person instanceof Wrestler ? $person->name : $person->full_name])->all();
    }

    public function render(TalentBookingsViewModel $bookings): View
    {
        $event = $this->event();
        Gate::authorize('update', $event);
        $booking = $event->talentBookings()->where('person_type', $this->personType)->where('person_id', $this->personId)->first();
        $history = $this->historyOpen && $booking !== null ? $booking->revisions()->orderByDesc('version')->paginate(10, pageName: 'historyPage') : null;
        $data = $bookings->data($event, $booking, $history ?? []);
        $rows = array_values(array_filter($data['rows'], fn (array $row): bool => $this->filter === 'all'
            || ($this->filter === 'attention' && $row['warnings'] !== []) || ($row['booking']?->status->value ?? 'not_tracked') === $this->filter));

        return view('livewire.events.components.talent-bookings', [...$data, 'rows' => $rows, 'event' => $event, 'booking' => $booking, 'history' => $history]);
    }

    private function event(): Event
    {
        return Event::query()->with('promotion')->findOrFail($this->eventId);
    }
}
