<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Enums\Matches\MatchType;
use App\Models\Events\Event;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\CompetitorSelection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validates the competitors a user chose in the match form before their booking history is read: the shape fits the
 * match type, every side has someone, and each wrestler or tag team belongs to the event's promotion. It returns what
 * was chosen, read once, for the history reads.
 */
final class BookingHistorySelection
{
    /**
     * @param  array<int, array{wrestlers?: array<int>, tag_teams?: array<int>}>  $sides
     *
     * @throws ValidationException
     */
    public function validate(Event $event, MatchType $type, array $sides): CompetitorSelection
    {
        // An event without a promotion has no history to read; the history query reports it as unavailable.
        if ($event->promotion_id === null) {
            return new CompetitorSelection([]);
        }

        Validator::make(['competitors' => $sides], [
            'competitors' => ['required', 'array', 'list', 'size:'.($type->usesIndividualCompetitorSides() ? 1 : $type->numberOfSides())],
            'competitors.*' => ['required', 'array:wrestlers,tag_teams'],
            'competitors.*.wrestlers' => ['sometimes', 'array', 'list'],
            'competitors.*.tag_teams' => ['sometimes', 'array', 'list'],
            'competitors.*.wrestlers.*' => ['integer', 'min:1', 'distinct'],
            'competitors.*.tag_teams.*' => ['integer', 'min:1', 'distinct'],
        ])->validate();

        $selection = CompetitorSelection::fromSides($type, $sides);
        $ids = $selection->idsByType();

        $hasEmptySide = array_filter($sides, fn (array $entries): bool => ($entries['wrestlers'] ?? []) === [] && ($entries['tag_teams'] ?? []) === []) !== [];

        if (array_diff(array_keys($ids), $type->getAllowedCompetitorTypes()) !== []
            || $hasEmptySide
            || count($selection->entries()) < $type->getMinimumCompetitors()) {
            throw ValidationException::withMessages(['history' => __('matches.history.selection_required')]);
        }

        foreach ([Wrestler::class, TagTeam::class] as $model) {
            $wanted = array_values(array_unique($ids[(new $model)->getMorphClass()] ?? []));
            $found = $model::query()->withTrashed()->where('promotion_id', $event->promotion_id)->whereKey($wanted)->count();

            if ($found !== count($wanted)) {
                throw ValidationException::withMessages(['history' => __('matches.history.unavailable_selection')]);
            }
        }

        return $selection;
    }
}
