<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Enums\MatchType;

final readonly class MatchCompetitorRuleSet
{
    public function __construct(private ?MatchType $matchType) {}

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        if ($this->matchType === null) {
            return $this->unselectedMatchTypeRules();
        }

        if ($this->matchType->usesIndividualCompetitorSides()) {
            return $this->individualEntrantRules($this->matchType);
        }

        return match ($this->matchType) {
            MatchType::Singles => $this->individualSideRules(2),
            MatchType::TripleThreat,
            MatchType::Triangle => $this->individualSideRules(3),
            MatchType::Fatal4Way => $this->individualSideRules(4),
            MatchType::TagTeam,
            MatchType::SixManTagTeam,
            MatchType::EightManTagTeam,
            MatchType::TenManTagTeam,
            MatchType::TornadoTagTeam => $this->tagTeamRules(),
            default => $this->genericRules(),
        };
    }

    /** @return array<string, array<int, mixed>> */
    private function unselectedMatchTypeRules(): array
    {
        return [
            'competitors' => ['sometimes', 'array'],
            'competitors.*.wrestlers' => ['sometimes', 'array'],
            'competitors.*.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            'competitors.*.tag_teams' => ['sometimes', 'array'],
            'competitors.*.tag_teams.*' => ['integer', 'exists:tag_teams,id'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function individualSideRules(int $sideCount): array
    {
        $rules = ['competitors' => ['required', 'array', "size:{$sideCount}"]];

        foreach (range(0, $sideCount - 1) as $sideIndex) {
            $rules["competitors.{$sideIndex}.wrestlers"] = ['required', 'array', 'size:1'];
            $rules["competitors.{$sideIndex}.wrestlers.*"] = ['integer', 'exists:wrestlers,id'];
        }

        return $rules;
    }

    /** @return array<string, array<int, mixed>> */
    private function tagTeamRules(): array
    {
        $rules = ['competitors' => ['required', 'array', 'size:2']];

        foreach (range(0, 1) as $sideIndex) {
            $rules["competitors.{$sideIndex}"] = ['required', 'array'];
            $rules["competitors.{$sideIndex}.wrestlers"] = ['sometimes', 'array', 'min:2'];
            $rules["competitors.{$sideIndex}.wrestlers.*"] = ['integer', 'exists:wrestlers,id'];
            $rules["competitors.{$sideIndex}.tag_teams"] = ['sometimes', 'array', 'min:1'];
            $rules["competitors.{$sideIndex}.tag_teams.*"] = ['integer', 'exists:tag_teams,id'];
        }

        return $rules;
    }

    /** @return array<string, array<int, mixed>> */
    private function individualEntrantRules(MatchType $matchType): array
    {
        $wrestlerRules = ['required', 'array', 'min:'.$matchType->getMinimumCompetitors()];
        $maximumCompetitors = $matchType->getMaximumCompetitors();

        if ($maximumCompetitors !== null) {
            $wrestlerRules[] = "max:{$maximumCompetitors}";
        }

        return [
            'competitors' => ['required', 'array', 'size:1'],
            'competitors.0.wrestlers' => $wrestlerRules,
            'competitors.0.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function genericRules(): array
    {
        return [
            'competitors' => ['required', 'array', 'min:2'],
            'competitors.*.wrestlers' => ['sometimes', 'array'],
            'competitors.*.wrestlers.*' => ['integer', 'exists:wrestlers,id'],
            'competitors.*.tag_teams' => ['sometimes', 'array'],
            'competitors.*.tag_teams.*' => ['integer', 'exists:tag_teams,id'],
        ];
    }
}
