<?php

declare(strict_types=1);

namespace App\Livewire\Roster\Availability\Pages;

use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\EventRosterAvailabilityQuery;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Calendar extends Component
{
    use WithPagination;

    #[Url]
    public string $date = '';

    #[Url]
    public string $kind = '';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Wrestler::class);
        if ($this->date === '') {
            $this->date = now()->setTimezone(Promotion::zoneOf(app(PromotionContextService::class)->current()))->toDateString();
        }
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function moveDay(int $direction): void
    {
        Gate::authorize('viewAny', Wrestler::class);
        if (! in_array($direction, [-1, 1], true) || ! $this->validDate()) {
            return;
        }
        $this->date = Carbon::parse($this->date, 'UTC')->addDays($direction)->toDateString();
        $this->resetPage();
    }

    private function validDate(): bool
    {
        if (Validator::make(['date' => $this->date], ['date' => ['required', 'date_format:Y-m-d']])->fails()) {
            $this->addError('date', __('availability.invalid_day'));

            return false;
        }
        $this->resetErrorBag('date');

        return true;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Wrestler::class);
        $promotion = app(PromotionContextService::class)->current();
        $entries = null;
        $eventIssues = [];
        if ($promotion !== null && $this->validDate()) {
            $needle = mb_trim(str_replace(['%', '_', '\\'], '', $this->search));
            $types = match ($this->kind) {
                'wrestlers' => [Wrestler::class],
                'referees' => [Referee::class],
                default => [Wrestler::class, Referee::class],
            };
            $entries = Unavailability::query()->where('promotion_id', $promotion->id)->covering($this->date)->with('person')
                ->whereHasMorph('person', $types, function (Builder $query, string $type) use ($needle): void {
                    if ($needle !== '') {
                        $query->whereLike($type === Referee::class ? 'full_name' : 'name', "%{$needle}%", caseSensitive: false);
                    }
                })->orderBy('starts_on')->orderBy('id')->paginate(20);
            $start = Carbon::parse($this->date, $promotion->timezone)->startOfDay();
            $events = Event::query()->where('promotion_id', $promotion->id)->where('date', '>=', $start->copy()->utc())
                ->where('date', '<', $start->copy()->addDay()->utc())->orderBy('date')->orderBy('id')->get();
            foreach ($events as $event) {
                $people = resolve(EventRosterAvailabilityQuery::class)->unavailable($event);
                if ($people->isNotEmpty()) {
                    $eventIssues[] = ['event' => $event, 'names' => $people->map(fn (Wrestler|Referee $person): string => $person instanceof Referee ? $person->full_name : $person->name)->join(', ')];
                }
            }
        }

        return view('livewire.roster.availability.pages.calendar', ['promotion' => $promotion, 'entries' => $entries, 'eventIssues' => $eventIssues]);
    }
}
