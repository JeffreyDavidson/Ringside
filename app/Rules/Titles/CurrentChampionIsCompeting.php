<?php

declare(strict_types=1);

namespace App\Rules\Titles;

use App\Enums\Titles\TitleType;
use App\Models\Events\Event;
use App\Models\Titles\TitleChampionship;
use App\ValueObjects\CompetitorSelection;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class CurrentChampionIsCompeting implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** The champion that must compete held the title on the booked event's date; without a date it is the current one. */
    public function __construct(private readonly ?Event $event = null) {}

    /** @param array<string, mixed> $data */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
            return;
        }

        $currentChampionship = TitleChampionship::query()
            ->forTitleId((int) $value)
            ->heldOn($this->event?->date?->copy()->utc())
            ->first(['champion_type', 'champion_id']);

        if (! $currentChampionship instanceof TitleChampionship) {
            return;
        }

        $championType = TitleType::tryFromChampionMorphClass($currentChampionship->champion_type)?->championMorphClass();

        if ($championType !== null && $this->includesChampion($championType, $currentChampionship->champion_id)) {
            return;
        }

        $fail(__('titles.validation.champion_must_compete'));
    }

    private function includesChampion(int|string $championType, int $championId): bool
    {
        $idsByType = CompetitorSelection::fromInput($this->data['competitors'] ?? null)->idsByType();

        return in_array($championId, $idsByType[$championType] ?? [], true);
    }
}
