<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Livewire\Base\BaseForm;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Shared\CanChangeDebutDate;
use App\Rules\Stables\CanJoinStable;
use App\Rules\Stables\HasMinimumMembers;
use App\Rules\Wrestlers\IsNotInjured;
use App\Rules\Wrestlers\NotRepresentedBySelectedTagTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** @extends BaseForm<Stable> */
class CreateEditForm extends BaseForm
{
    public string $name = '';

    /** Kept as a string so Livewire does not auto-cast the date. */
    public ?string $started_at = null;

    /** Kept as a string so Livewire does not auto-cast the date. */
    public ?string $ended_at = null;

    /** @var array<int> */
    public array $wrestlers = [];

    /** @var array<int> */
    public array $tag_teams = [];

    private StableActivityEligibility $activityEligibility;

    public function boot(StableActivityEligibility $activityEligibility): void
    {
        $this->activityEligibility = $activityEligibility;
    }

    protected function loadModelData(Model $model): void
    {
        $this->started_at = $this->localDateString($model->firstActivityPeriod?->started_at);
        $this->ended_at = $this->localDateString($model->firstActivityPeriod?->ended_at);
        $this->wrestlers = $model->currentWrestlers->modelKeys();
        $this->tag_teams = $model->currentTagTeams->modelKeys();
    }

    public function toData(): StableData
    {
        $members = $this->selectedMembers();

        return new StableData(
            name: $this->name,
            start_date: $this->started_at ? $this->parseLocalDate($this->started_at) : null,
            members: $members,
            end_date: $this->ended_at ? $this->parseLocalDate($this->ended_at) : null,
        );
    }

    public function stable(): Stable
    {
        return Stable::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        // Members of an existing stable join today (see UpdateAction); members of a new stable join on its start date.
        $memberJoinDate = $this->isEditing() ? now() : $this->parseStartDate();
        $members = $this->selectedMembers();
        $stable = $this->isEditing() ? $this->stable() : null;
        $canHaveMembers = ! $stable instanceof Stable || $this->activityEligibility->canHaveMembers($stable);

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                $this->uniqueInPromotion('stables', 'name')->withoutTrashed(),
            ],
            'started_at' => [
                'nullable',
                'date',
                new CanChangeDebutDate($stable),
            ],
            'ended_at' => ['nullable', 'date', 'prohibited'],
            'wrestlers' => ['nullable', 'array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                $this->existsInPromotion('wrestlers'),
                new CanJoinStable(Wrestler::class, $this->stableId(), $memberJoinDate),
                new IsNotInjured,
                new NotRepresentedBySelectedTagTeam(collect($this->tag_teams)),
            ],
            'tag_teams' => ['nullable', 'array'],
            'tag_teams.*' => [
                'bail',
                'integer',
                $this->existsInPromotion('tag_teams'),
                new CanJoinStable(TagTeam::class, $this->stableId(), $memberJoinDate),
            ],
        ];

        if ($canHaveMembers) {
            if ($this->mustMeetMinimumMembers($stable)) {
                $rules['started_at'][] = new HasMinimumMembers(
                    $members->wrestlers,
                    $members->tagTeams,
                );
            }
        } else {
            $rules['wrestlers'][] = 'prohibited';
            $rules['tag_teams'][] = 'prohibited';
        }

        if ($stable?->firstActivityPeriod?->ended_at !== null) {
            $rules['ended_at'] = ['required', 'date'];
        }

        if (! in_array($this->started_at, [null, '', '0'], true) && ! in_array($this->ended_at, [null, '', '0'], true)) {
            $rules['ended_at'][] = 'after_or_equal:started_at';
        }

        return $rules;
    }

    /**
     * The minimum applies when a stable is created or established and whenever the member list is changed; an
     * established stable that already fell below it (members left, see the membership docs) can still be renamed.
     */
    private function mustMeetMinimumMembers(?Stable $stable): bool
    {
        if (! $stable instanceof Stable || $stable->firstActivityPeriod === null) {
            return true;
        }

        return $this->sortedKeys($this->wrestlers) !== $this->sortedKeys($stable->currentWrestlers->modelKeys())
            || $this->sortedKeys($this->tag_teams) !== $this->sortedKeys($stable->currentTagTeams->modelKeys());
    }

    /**
     * @param  array<int|string>  $keys
     * @return array<int>
     */
    private function sortedKeys(array $keys): array
    {
        $keys = array_map(intval(...), $keys);
        sort($keys);

        return $keys;
    }

    /**
     * Load the members selected by the form into the shared stable data object.
     */
    private function selectedMembers(): StableMembershipData
    {
        return new StableMembershipData(
            wrestlers: Wrestler::query()->whereKey($this->wrestlers)->orderBy('id')->get(),
            tagTeams: TagTeam::query()->whereKey($this->tag_teams)->orderBy('id')->get(),
        );
    }

    private function parseStartDate(): ?Carbon
    {
        if ($this->started_at === null || strtotime($this->started_at) === false) {
            return null;
        }

        return $this->parseLocalDate($this->started_at);
    }

    private function stableId(): ?int
    {
        return $this->modelId === null ? null : (int) $this->modelId;
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'ended_at.required' => __('stables.validation.ended_at_required'),
            'ended_at.prohibited' => __('stables.validation.ended_at_prohibited'),
            'wrestlers.prohibited' => __('stables.validation.members_prohibited'),
            'tag_teams.prohibited' => __('stables.validation.members_prohibited'),
        ];
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'started_at' => __('stables.validation.attributes.start_date'),
            'ended_at' => __('stables.validation.attributes.end_date'),
        ];
    }
}
