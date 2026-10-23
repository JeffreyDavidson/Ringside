<?php

declare(strict_types=1);

namespace App\Livewire\Events\Components;

use App\Actions\Events\SaveEventProductionAction;
use App\Data\Events\EventProductionData;
use App\Exceptions\BaseBusinessException;
use App\Lifecycle\Events\EventProductionRequirements;
use App\Livewire\Concerns\ExplainsKeptInput;
use App\Livewire\Concerns\ResolvesActor;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Queries\Crew\CrewContactSearchQuery;
use App\Services\Events\EventPackSourceService;
use App\ValueObjects\ProductionPlan;
use App\ViewModels\Events\EventPackViewModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @phpstan-import-type Assignment from EventPack
 * @phpstan-import-type Cue from EventPack
 * @phpstan-import-type RunningRow from EventPackSourceService
 */
class Production extends Component
{
    use ExplainsKeptInput;
    use ResolvesActor;

    #[Locked]
    public int $eventId;

    #[Locked]
    public int $version = 0;

    #[Locked]
    public string $fingerprint = '';

    /** @var list<RunningRow> */
    #[Locked]
    public array $items = [];

    /** @var list<Assignment> */
    public array $assignments = [];

    /** @var list<Cue> */
    public array $cues = [];

    /** @var list<string> */
    public array $refreshNames = [];

    /** @var array<string, string> */
    public array $retainedTargets = [];

    public string $notes = '';

    public string $search = '';

    public bool $reviewed = false;

    public bool $saved = false;

    protected EventPackViewModel $packs;

    public function boot(EventPackViewModel $packs): void
    {
        $this->packs = $packs;
    }

    public function mount(int $eventId): void
    {
        $this->eventId = $eventId;
        $this->reloadSaved();
    }

    public function reloadSaved(): void
    {
        $event = $this->event();
        Gate::authorize('updateProduction', $event);
        $data = $this->packs->data($event);
        $this->version = $data['pack']->version ?? 0;
        $this->fingerprint = $data['card']['fingerprint'];
        $this->items = $data['running'];
        $production = ProductionPlan::fromArray($data['content']['production'] ?? null);
        $this->assignments = $production->activeAssignments();
        $this->cues = array_values(array_filter($data['content']['production']['cues'] ?? [], fn (array $cue): bool => in_array($cue['item_key'], array_column($this->items, 'key'), true)));
        $this->notes = $production->notes() ?? '';
        $this->reviewed = false;
        $this->refreshNames = [];
        $this->retainedTargets = [];
        $this->resetErrorBag();
        $this->dispatch('production-saved');
    }

    public function updated(): void
    {
        Gate::authorize('updateProduction', $this->event());
        $this->saved = false;
    }

    public function addAssignment(): void
    {
        $event = $this->event();
        Gate::authorize('updateProduction', $event);

        if (count($this->assignments) >= 100) {
            $this->addError('production', __('production.assignment_limit'));

            return;
        }
        $data = $this->packs->data($event);
        $this->assignments[] = ['id' => (string) Str::uuid(), 'contact_id' => 0, 'name' => '', 'role' => 'sound',
            'station' => null, 'call_at' => null, 'timezone' => $data['content']['timezone'], 'offset' => null,
            'setup' => null, 'during' => null, 'strike' => null, 'active' => true];
        $this->saved = false;
    }

    public function removeAssignment(string $id): void
    {
        Gate::authorize('updateProduction', $this->event());
        $this->assignments = array_values(array_filter($this->assignments, fn (array $entry): bool => $entry['id'] !== $id));
        $this->saved = false;
    }

    public function refreshName(string $id): void
    {
        Gate::authorize('updateProduction', $this->event());
        $this->refreshNames[] = $id;
        $this->saved = false;
    }

    public function addCue(string $key): void
    {
        Gate::authorize('updateProduction', $this->event());
        Validator::make(['item' => $key], ['item' => ['required', Rule::in(array_column($this->items, 'key'))]])->validate();

        if (count(array_filter($this->cues, fn (array $cue): bool => $cue['item_key'] === $key)) >= 20) {
            $this->addError('production', __('production.cue_limit'));

            return;
        }
        $this->cues[] = ['id' => (string) Str::uuid(), 'item_key' => $key, 'subject' => '', 'trigger' => '', 'instruction' => '',
            'role' => null, 'owner_id' => null, 'owner_name' => null, 'owner_station' => null, 'owner_role' => null, 'owner_contact_id' => null, 'pronunciation' => null, 'track' => null, 'url' => null];
        $this->saved = false;
    }

    public function removeCue(string $id): void
    {
        Gate::authorize('updateProduction', $this->event());
        $this->cues = array_values(array_filter($this->cues, fn (array $cue): bool => $cue['id'] !== $id));
        $this->saved = false;
    }

    public function restoreCue(string $id): void
    {
        $event = $this->event();
        Gate::authorize('updateProduction', $event);
        $data = $this->packs->data($event);
        $retained = array_values(array_filter($data['content']['production']['cues'] ?? [], fn (array $cue): bool => ! in_array($cue['item_key'], array_column($this->items, 'key'), true)));
        $key = $this->retainedTargets[$id] ?? '';
        Validator::make(['cue' => $id, 'item' => $key], [
            'cue' => ['required', Rule::in(array_column($retained, 'id'))],
            'item' => ['required', Rule::in(array_column($this->items, 'key'))],
        ])->validate();

        foreach ($retained as $cue) {
            if ($cue['id'] === $id) {
                $cue['item_key'] = $key;
                $this->cues = [...array_values(array_filter($this->cues, fn (array $entry): bool => $entry['id'] !== $id)), $cue];
            }
        }
        $this->saved = false;
    }

    public function moveCue(string $id, int $direction): void
    {
        Gate::authorize('updateProduction', $this->event());
        Validator::make(['direction' => $direction], ['direction' => [Rule::in([-1, 1])]])->validate();
        $index = array_search($id, array_column($this->cues, 'id'), true);

        if (! is_int($index)) {
            return;
        }
        $indices = array_keys(array_filter($this->cues, fn (array $cue): bool => $cue['item_key'] === $this->cues[$index]['item_key']));
        $position = array_search($index, $indices, true);
        $other = $indices[$position + $direction] ?? null;

        if ($other === null) {
            return;
        }
        [$this->cues[$index], $this->cues[$other]] = [$this->cues[$other], $this->cues[$index]];
        $this->saved = false;
    }

    public function save(SaveEventProductionAction $action, EventProductionRequirements $requirements): void
    {
        $event = $this->event();
        Gate::authorize('updateProduction', $event);
        $this->resetErrorBag();
        $previousVersion = $this->version;

        try {
            $input = $this->validatedInput($requirements);
            $action->handle($event, $this->version, $this->fingerprint, EventProductionData::fromValidated($input), $this->reviewed, $this->actor());
            $this->reloadSaved();
            $this->saved = true;
            $this->dispatch('production-pack-saved', previousVersion: $previousVersion, version: $this->version, fingerprint: $this->fingerprint);
        } catch (BaseBusinessException $exception) {
            $this->addError('production', $this->failureMessageKeepingInput($exception));
        }
    }

    public function render(CrewContactSearchQuery $search): View
    {
        $event = $this->event();
        Gate::authorize('updateProduction', $event);
        $data = $this->packs->data($event);
        $contacts = $search->get($this->search, $event->promotion_id);
        $selected = CrewContact::query()->where('promotion_id', $event->promotion_id)->whereKey(array_column($this->assignments, 'contact_id'))->get();

        return view('livewire.events.components.production', ['event' => $event, 'data' => $data,
            'contacts' => $contacts->merge($selected)->sortBy('name')->values(),
            'missingCalls' => count(array_filter($this->assignments, fn (array $entry): bool => ! ($entry['call_at'] ?? null))),
            'missingOwners' => count(array_filter($this->cues, fn (array $cue): bool => ! ($cue['owner_id'] ?? null) || ! in_array($cue['owner_id'], array_column($this->assignments, 'id'), true)))]);
    }

    /** @return array<string, mixed> */
    private function validatedInput(EventProductionRequirements $requirements): array
    {
        return $requirements->validatedInput([
            'notes' => $this->notes,
            'assignments' => array_map(fn (array $assignment): array => [...$assignment, 'refresh_name' => in_array($assignment['id'], $this->refreshNames, true)], $this->assignments),
            'cues' => $this->cues,
        ]);
    }

    private function event(): Event
    {
        return Event::query()->findOrFail($this->eventId);
    }
}
