<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Forms;

use App\Data\TagTeams\TagTeamData;
use App\Livewire\Base\BaseForm;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Managers\IsAssignable;
use App\Rules\Shared\CanChangeEmploymentDate;
use App\Rules\Wrestlers\CanJoinTagTeam;
use Illuminate\Database\Eloquent\Model;
use Override;

/** @extends BaseForm<TagTeam> */
class CreateEditForm extends BaseForm
{
    public string $name = '';

    public ?string $signature_move = '';

    public ?int $wrestlerA = null;

    public ?int $wrestlerB = null;

    /** @var array<int, int> */
    public array $managers = [];

    /** Kept as a string so Livewire does not auto-cast the date. */
    public ?string $employment_date = '';

    protected function loadModelData(Model $model): void
    {
        if ($model->employments()->exists()) {
            $this->employment_date = $this->localDateString($model->firstEmployment?->started_at);
        }

        $currentWrestlers = $model->currentWrestlers->sortBy('id')->values();
        $this->wrestlerA = $currentWrestlers->first()?->id;
        $this->wrestlerB = $currentWrestlers->skip(1)->first()?->id;

        $this->managers = $model->currentManagers
            ->map(fn (Manager $manager): int => $manager->id)
            ->all();
    }

    public function toData(): TagTeamData
    {
        return new TagTeamData(
            name: $this->name,
            signature_move: $this->signature_move ?: null,
            employment_date: $this->employment_date ? $this->parseLocalDate($this->employment_date) : null,
            wrestlerA: Wrestler::query()->findOrFail($this->wrestlerA),
            wrestlerB: Wrestler::query()->findOrFail($this->wrestlerB),
            managers: Manager::query()->whereKey($this->managers)->get(),
        );
    }

    public function tagTeam(): TagTeam
    {
        return TagTeam::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        $tagTeam = $this->isEditing() ? $this->tagTeam() : null;

        return [
            'name' => ['required', 'string', 'max:255', $this->uniqueInPromotion('tag_teams', 'name')],
            'signature_move' => ['nullable', 'string', 'max:255', $this->uniqueInPromotion('tag_teams', 'signature_move')],
            'wrestlerA' => ['bail', 'required', 'integer', $this->existsInPromotion('wrestlers'), new CanJoinTagTeam($this->modelId)],
            'wrestlerB' => ['bail', 'required', 'integer', $this->existsInPromotion('wrestlers'), 'different:wrestlerA', new CanJoinTagTeam($this->modelId)],
            'managers' => ['array'],
            'managers.*' => ['bail', 'integer', $this->existsInPromotion('managers'), new IsAssignable($this->currentManagerIds($tagTeam))],
            'employment_date' => ['nullable', 'date', new CanChangeEmploymentDate($tagTeam)],
        ];
    }

    /**
     * The managers the edited tag team already has: they stay on it even if they are no longer available.
     *
     * @return array<int, int>
     */
    private function currentManagerIds(?TagTeam $tagTeam): array
    {
        return $tagTeam?->currentManagers()->get()->map(fn (Manager $manager): int => $manager->id)->all() ?? [];
    }

    #[Override]
    protected function validationAttributes(): array
    {
        return [
            'signature_move' => __('tag-teams.validation.attributes.signature_move'),
            'wrestlerA' => __('tag-teams.validation.attributes.first_wrestler'),
            'wrestlerB' => __('tag-teams.validation.attributes.second_wrestler'),
            'managers' => __('tag-teams.validation.attributes.managers'),
            'managers.*' => __('tag-teams.validation.attributes.manager'),
            'employment_date' => __('tag-teams.validation.attributes.employment_date'),
        ];
    }
}
