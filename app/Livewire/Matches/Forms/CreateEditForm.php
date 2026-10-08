<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Forms;

use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Livewire\Base\BaseForm;
use App\Livewire\Matches\Enums\CompetitorSelectionLayout;
use App\Livewire\Matches\Support\MatchCompetitorRuleSet;
use App\Livewire\Matches\Support\MatchCompetitorStateMapper;
use App\Models\Events\Event;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use LogicException;

/** @extends BaseForm<EventMatch> */
class CreateEditForm extends BaseForm
{
    private MatchCompetitorStateMapper $competitorStateMapper;

    /** The booked event's promotion, set by validateForEvent() for the current request only. */
    private ?int $eventPromotionId = null;

    public ?string $preview = '';

    public ?MatchType $matchType = null;

    public ?int $matchStipulationId = null;

    /** @var array<int, array{wrestlers?: array<int>, tag_teams?: array<int>}> */
    public array $competitors = [];

    /** @var array<int> */
    public array $referees = [];

    /** @var array<int> */
    public array $titles = [];

    public function boot(MatchCompetitorStateMapper $competitorStateMapper): void
    {
        $this->competitorStateMapper = $competitorStateMapper;
    }

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

    protected function loadModelData(Model $model): void
    {
        $this->matchType = $model->match_type;
        $this->matchStipulationId = $model->match_stipulation_id;

        $this->referees = $model->referees()
            ->orderBy('referees.id')
            ->get()
            ->map(fn (Referee $referee): int => $referee->id)
            ->all();
        $this->titles = $model->titles()
            ->orderBy('titles.id')
            ->get()
            ->map(fn (Title $title): int => $title->id)
            ->all();
        $sides = $model->sides()
            ->with(['competitors' => fn (Relation $competitors): Relation => $competitors->orderBy('id'), 'competitors.competitor'])
            ->get();
        $this->competitors = $this->competitorStateMapper->fromSides(
            $sides,
            $this->requiredMatchType()->usesIndividualCompetitorSides(),
        );
    }

    /** Validate the selection against the booked event: only its promotion's roster and titles are accepted. */
    public function validateForEvent(Event $event): void
    {
        $this->eventPromotionId = $event->promotion_id;

        $this->validate();
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
                        'wrestlers' => Wrestler::query()->whereKey($side['wrestlers'] ?? [])->orderBy('id')->get()->all(),
                        'tag_teams' => TagTeam::query()->whereKey($side['tag_teams'] ?? [])->orderBy('id')->get()->all(),
                    ],
                ]);

        return new EventMatchData(
            matchType: $matchType,
            referees: Referee::query()->whereKey($this->referees)->orderBy('id')->get(),
            titles: Title::query()->whereKey($this->titles)->orderBy('id')->get(),
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
            'referees.*' => ['bail', 'integer', $this->existsInPromotion('referees'), new RefereeIsBookable],
            'titles' => ['sometimes', 'array'],
            'titles.*' => [
                'bail',
                'integer',
                $this->existsInPromotion('titles'),
                new IsActive,
                new MatchesCompetitorType,
                new CurrentChampionIsCompeting,
            ],
        ];

        return array_merge($baseRules, new MatchCompetitorRuleSet($this->matchType, $this->formPromotionId())->rules());
    }

    /** A match has no promotion of its own: it books the roster and titles of its event's promotion. */
    #[\Override]
    public function formPromotionId(): ?int
    {
        return $this->eventPromotionId;
    }

    private function requiredMatchType(): MatchType
    {
        return $this->matchType
            ?? throw new LogicException('A match type is required before building match data.');
    }

    /**
     * The name the form shows for a competitor side, and uses for it in validation messages.
     */
    public function sideLabel(int $index): string
    {
        return match ($this->competitorSelectionLayout()) {
            CompetitorSelectionLayout::TagTeam => __('matches.form.team', ['team' => mb_chr(ord('A') + $index)]),
            CompetitorSelectionLayout::BattleRoyal => __('matches.competitors'),
            CompetitorSelectionLayout::Generic, null => __('matches.form.side', ['number' => $index + 1]),
            default => __('matches.form.competitor', ['number' => $index + 1]),
        };
    }

    /**
     * An empty pick list means nothing of that kind was chosen. The roster comboboxes always send
     * both lists, so without this a tag team side would also be asked for wrestlers and vice versa.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    #[\Override]
    protected function prepareForValidation(mixed $attributes): array
    {
        $attributes['competitors'] = array_map(
            fn (mixed $side): mixed => is_array($side)
                ? array_filter($side, fn (mixed $ids): bool => $ids !== [])
                : $side,
            (array) $attributes['competitors'],
        );

        return $attributes;
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        $competitorMessages = match ($this->competitorSelectionLayout()) {
            CompetitorSelectionLayout::TagTeam => [
                'competitors.*.required' => __('matches.validation.side_required'),
                'competitors.*.wrestlers.min' => __('matches.validation.tag_team_side_min'),
            ],
            CompetitorSelectionLayout::BattleRoyal => [
                'competitors.0.wrestlers.required' => __('matches.validation.entrants_required'),
                'competitors.0.wrestlers.min' => __('matches.validation.entrants_min'),
                'competitors.0.wrestlers.max' => __('matches.validation.entrants_max'),
            ],
            CompetitorSelectionLayout::Generic => [
                'competitors.*.wrestlers.required_without' => __('matches.validation.side_required'),
            ],
            default => [
                'competitors.*.wrestlers.required' => __('matches.validation.competitor_required'),
                'competitors.*.wrestlers.required_without' => __('matches.validation.competitor_required'),
            ],
        };

        return [
            ...$competitorMessages,
            'competitors.*.wrestlers.*.distinct' => __('matches.validation.wrestler_distinct'),
            'competitors.*.tag_teams.*.distinct' => __('matches.validation.tag_team_distinct'),
        ];
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        $attributes = [
            'preview' => __('matches.validation.attributes.match_preview'),
            'matchType' => __('matches.validation.attributes.match_type'),
            'matchStipulationId' => __('matches.validation.attributes.match_stipulation'),
            'competitors' => __('matches.validation.attributes.competitors'),
            'competitors.*.wrestlers.*' => __('matches.validation.attributes.wrestler'),
            'competitors.*.tag_teams.*' => __('matches.validation.attributes.tag_team'),
            'referees' => __('matches.validation.attributes.referees'),
            'referees.*' => __('matches.validation.attributes.referee'),
            'titles' => __('matches.validation.attributes.championship_titles'),
            'titles.*' => __('matches.validation.attributes.title'),
        ];

        foreach (array_keys(array_values($this->competitors)) as $index) {
            $side = $this->sideLabel($index);
            $attributes["competitors.{$index}"] = $side;
            $attributes["competitors.{$index}.wrestlers"] = $side;
            $attributes["competitors.{$index}.tag_teams"] = $side;
        }

        return $attributes;
    }

    private function competitorSelectionLayout(): ?CompetitorSelectionLayout
    {
        return $this->matchType instanceof MatchType
            ? CompetitorSelectionLayout::forMatchType($this->matchType)
            : null;
    }
}
