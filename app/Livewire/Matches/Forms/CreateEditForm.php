<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Forms;

use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Livewire\Base\BaseForm;
use App\Livewire\Matches\Support\MatchCompetitorRuleSet;
use App\Livewire\Matches\Support\MatchCompetitorStateMapper;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Rules\Referees\IsBookable as RefereeIsBookable;
use App\Rules\Titles\CurrentChampionIsCompeting;
use App\Rules\Titles\IsActive;
use App\Rules\Titles\MatchesCompetitorType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use LogicException;

/** @extends BaseForm<EventMatch> */
class CreateEditForm extends BaseForm
{
    public ?string $preview = '';

    public ?MatchType $matchType = null;

    public ?int $matchStipulationId = null;

    /** @var array<int, array{wrestlers?: array<int>, tag_teams?: array<int>}> */
    public array $competitors = [];

    /** @var array<int> */
    public array $referees = [];

    /** @var array<int> */
    public array $titles = [];

    public function resetCompetitorsFor(MatchType $matchType): void
    {
        $sideCount = $matchType->usesIndividualCompetitorSides()
            ? 1
            : ($matchType->numberOfSides() ?? 1);

        $this->competitors = array_fill(0, $sideCount, [
            'wrestlers' => [],
            'tag_teams' => [],
        ]);
    }

    public function loadExtraData(): void
    {
        if (! $this->formModel instanceof EventMatch) {
            return;
        }

        $this->matchType = $this->formModel->match_type;
        $this->matchStipulationId = $this->formModel->match_stipulation_id;

        $this->referees = $this->formModel->referees
            ->map(fn (Referee $referee): int => $referee->id)
            ->all();
        $this->titles = $this->formModel->titles
            ->map(fn (Title $title): int => $title->id)
            ->all();
        $sides = $this->formModel->sides()
            ->with('competitors.competitor')
            ->get();
        $this->competitors = app(MatchCompetitorStateMapper::class)->fromSides(
            $sides,
            $this->requiredMatchType()->usesIndividualCompetitorSides(),
        );
    }

    public function toData(): EventMatchData
    {
        $matchType = $this->requiredMatchType();
        $sides = $matchType->usesIndividualCompetitorSides()
            ? collect($this->competitors[0]['wrestlers'] ?? [])
                ->values()
                ->mapWithKeys(fn (int $wrestlerId, int $index): array => [
                    $index + 1 => [
                        'wrestlers' => Wrestler::query()->whereKey($wrestlerId)->get()->all(),
                        'tag_teams' => [],
                    ],
                ])
            : collect($this->competitors)
                ->values()
                ->mapWithKeys(fn (array $side, int $index): array => [
                    $index + 1 => [
                        'wrestlers' => Wrestler::query()->whereKey($side['wrestlers'] ?? [])->get()->all(),
                        'tag_teams' => TagTeam::query()->whereKey($side['tag_teams'] ?? [])->get()->all(),
                    ],
                ]);

        return new EventMatchData(
            matchType: $matchType,
            referees: Referee::query()->whereKey($this->referees)->get(),
            titles: Title::query()->whereKey($this->titles)->get(),
            sides: $sides,
            preview: $this->preview,
            matchStipulation: $this->matchStipulationId === null
                ? null
                : MatchStipulation::query()->findOrFail($this->matchStipulationId),
        );
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $baseRules = [
            'matchType' => ['required', new Enum(MatchType::class)],
            'matchStipulationId' => [
                'nullable',
                'integer',
                Rule::exists(MatchStipulation::class, 'id')->where('is_active', true),
            ],
            'preview' => ['sometimes', 'string'],
            'referees' => ['required', 'array', 'min:1'],
            'referees.*' => ['bail', 'integer', 'exists:referees,id', new RefereeIsBookable()],
            'titles' => ['sometimes', 'array'],
            'titles.*' => [
                'bail',
                'integer',
                'exists:titles,id',
                new IsActive(),
                new MatchesCompetitorType(),
                new CurrentChampionIsCompeting(),
            ],
        ];

        return array_merge($baseRules, (new MatchCompetitorRuleSet($this->matchType))->rules());
    }

    private function requiredMatchType(): MatchType
    {
        return $this->matchType
            ?? throw new LogicException('A match type is required before building match data.');
    }

    /** @return array<string, string> */
    protected function getCustomValidationAttributes(): array
    {
        return [
            'preview' => 'match preview',
            'matchType' => 'match type',
            'matchStipulationId' => 'match stipulation',
            'competitors' => 'competitors',
            'referees' => 'referees',
            'titles' => 'championship titles',
        ];
    }
}
