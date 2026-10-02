<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Forms;

use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
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

    protected function loadModelData(Model $model): void
    {
        $this->started_at = $model->firstActivityPeriod?->started_at?->toDateString();
        $this->ended_at = $model->firstActivityPeriod?->ended_at?->toDateString();
        $this->wrestlers = $model->currentWrestlers->modelKeys();
        $this->tag_teams = $model->currentTagTeams->modelKeys();
    }

    public function toData(): StableData
    {
        $members = $this->selectedMembers();

        return new StableData(
            name: $this->name,
            start_date: $this->started_at ? Carbon::parse($this->started_at) : null,
            members: $members,
            end_date: $this->ended_at ? Carbon::parse($this->ended_at) : null,
        );
    }

    public function stable(): Stable
    {
        return Stable::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        $stableStartDate = $this->parseStartDate();
        $members = $this->selectedMembers();
        $stable = $this->isEditing() ? $this->stable() : null;

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
                new HasMinimumMembers(
                    $members->wrestlers ?? collect(),
                    $members->tagTeams ?? collect(),
                ),
            ],
            'ended_at' => ['nullable', 'date'],
            'wrestlers' => ['nullable', 'array'],
            'wrestlers.*' => [
                'bail',
                'integer',
                $this->existsInPromotion('wrestlers'),
                new CanJoinStable(Wrestler::class, $this->stableId(), $stableStartDate),
                new IsNotInjured,
                new NotRepresentedBySelectedTagTeam(collect($this->tag_teams)),
            ],
            'tag_teams' => ['nullable', 'array'],
            'tag_teams.*' => [
                'bail',
                'integer',
                $this->existsInPromotion('tag_teams'),
                new CanJoinStable(TagTeam::class, $this->stableId(), $stableStartDate),
            ],
        ];

        if (! in_array($this->started_at, [null, '', '0'], true) && ! in_array($this->ended_at, [null, '', '0'], true)) {
            $rules['ended_at'][] = 'after:started_at';
        }

        return $rules;
    }

    /**
     * Load the members selected by the form into the shared stable data object.
     */
    private function selectedMembers(): StableMembershipData
    {
        return new StableMembershipData(
            wrestlers: Wrestler::query()->whereKey($this->wrestlers)->get(),
            tagTeams: TagTeam::query()->whereKey($this->tag_teams)->get(),
        );
    }

    private function parseStartDate(): ?Carbon
    {
        if ($this->started_at === null || strtotime($this->started_at) === false) {
            return null;
        }

        return Carbon::parse($this->started_at);
    }

    private function stableId(): ?int
    {
        return $this->modelId === null ? null : (int) $this->modelId;
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'started_at' => 'start date',
            'ended_at' => 'end date',
        ];
    }
}
