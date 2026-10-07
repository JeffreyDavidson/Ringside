<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Builders\Roster\IndividualBuilder;
use App\Builders\Roster\StableBuilder;
use App\Builders\Roster\TagTeamBuilder;
use App\Builders\Titles\TitleBuilder;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * General Info card of a show page. Re-resolves the model, with the relationships the card
 * displays, whenever the entity's updated event fires so lifecycle actions show immediately.
 */
class GeneralInfo extends Component
{
    /**
     * Card definition per supported model: the event that refreshes it, the anonymous
     * Blade component that renders it, that component's model prop, the relationships it reads, and the lifecycle state projections its status badges read.
     *
     * @var array<class-string<Wrestler|Manager|Referee|Stable|TagTeam|Title>, array{event: string, component: string, prop: string, with: list<string>, state: list<string>}>
     */
    private const array CARDS = [
        Wrestler::class => [
            'event' => 'wrestler-updated',
            'component' => 'wrestlers.show.general-info',
            'prop' => 'wrestler',
            'with' => ['currentManagers', 'currentStable', 'currentTagTeam', 'currentChampionships.title', 'firstEmployment'],
            'state' => IndividualBuilder::ROSTER_STATE,
        ],
        Manager::class => [
            'event' => 'manager-updated',
            'component' => 'managers.show.general-info',
            'prop' => 'manager',
            'with' => ['currentTagTeams', 'currentWrestlers', 'firstEmployment'],
            'state' => IndividualBuilder::ROSTER_STATE,
        ],
        Referee::class => [
            'event' => 'referee-updated',
            'component' => 'referees.show.general-info',
            'prop' => 'referee',
            'with' => ['firstEmployment'],
            'state' => IndividualBuilder::ROSTER_STATE,
        ],
        Stable::class => [
            'event' => 'stable-updated',
            'component' => 'stables.show.general-info',
            'prop' => 'stable',
            'with' => ['currentTagTeams', 'currentWrestlers', 'firstActivityPeriod'],
            'state' => StableBuilder::ACTIVITY_STATUS_STATE,
        ],
        TagTeam::class => [
            'event' => 'tag-team-updated',
            'component' => 'tag-teams.show.general-info',
            'prop' => 'tagTeam',
            'with' => ['currentManagers', 'currentStable', 'currentWrestlers', 'currentChampionships.title'],
            'state' => TagTeamBuilder::ROSTER_STATE,
        ],
        Title::class => [
            'event' => 'title-updated',
            'component' => 'titles.show.general-info',
            'prop' => 'title',
            'with' => ['currentChampionship.champion', 'firstActivityPeriod'],
            'state' => TitleBuilder::ACTIVITY_STATUS_STATE,
        ],
    ];

    /** @var class-string<Wrestler|Manager|Referee|Stable|TagTeam|Title> */
    #[Locked]
    public string $modelClass;

    #[Locked]
    public int $modelId;

    public function mount(Wrestler|Manager|Referee|Stable|TagTeam|Title $model): void
    {
        $this->modelClass = $model::class;
        $this->modelId = $model->id;
    }

    /** @return array<string, string> */
    protected function getListeners(): array
    {
        return [self::CARDS[$this->modelClass]['event'] => '$refresh'];
    }

    public function render(): View
    {
        $card = self::CARDS[$this->modelClass];

        $model = $this->modelClass::query()->with($card['with'])->withExists($card['state'])->findOrFail($this->modelId);

        if ($model instanceof TagTeam) {
            $model->currentWrestlers->loadExists(IndividualBuilder::AVAILABILITY_STATE);
        }

        return view('livewire.components.general-info', [
            'component' => $card['component'],
            'props' => [$card['prop'] => $model],
        ]);
    }
}
