<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\Matches\MatchType;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

/**
 * The wrestlers and tag teams someone picked on the match form, each with the side it was picked on, read by the
 * booking history. It is a read-side value with no column: it validates its entries when built and answers the
 * questions the history reads ask (the ids per competitor type, who is on a side, who faces it).
 *
 * A side is the form side for match types with sides; a match type where every competitor stands alone gets one
 * side per competitor, in the order they were chosen.
 */
final readonly class CompetitorSelection
{
    /** @var list<array{type: string, id: int, side: int}> */
    private array $entries;

    /**
     * @param  array<int, array<string, mixed>>  $entries
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $entries)
    {
        $known = [Relation::getMorphAlias(Wrestler::class), Relation::getMorphAlias(TagTeam::class)];
        $checked = [];

        foreach ($entries as $entry) {
            if (! is_string($entry['type'] ?? null) || ! in_array($entry['type'], $known, true)) {
                throw new InvalidArgumentException('A selected competitor must be a wrestler or a tag team.');
            }

            if (! is_int($entry['id'] ?? null) || ! is_int($entry['side'] ?? null)) {
                throw new InvalidArgumentException('A selected competitor needs an integer id and an integer side.');
            }

            $checked[] = ['type' => $entry['type'], 'id' => $entry['id'], 'side' => $entry['side']];
        }

        $this->entries = $checked;
    }

    /**
     * Read the wrestlers and tag teams chosen on each side of the match form, in the order they were chosen. The form
     * can hand over ids as numeric strings, so each id is cast.
     *
     * @param  array<int, array{wrestlers?: array<int|string>, tag_teams?: array<int|string>}>  $sides
     */
    public static function fromSides(MatchType $type, array $sides): self
    {
        $entries = [];

        foreach ($sides as $side => $chosen) {
            foreach (['wrestlers' => Wrestler::class, 'tag_teams' => TagTeam::class] as $kind => $model) {
                foreach ($chosen[$kind] ?? [] as $id) {
                    $entries[] = [
                        'type' => Relation::getMorphAlias($model),
                        'id' => (int) $id,
                        'side' => $type->usesIndividualCompetitorSides() ? count($entries) : $side,
                    ];
                }
            }
        }

        return new self($entries);
    }

    /**
     * Read the competitors from submitted form input that has not been validated yet, so a validation rule can ask
     * the same questions as the booking history. Anything that is not a side holding `wrestlers` or `tag_teams`
     * lists is skipped, sides are numbered in the order they appear, and an entry that is not an int or string id
     * still counts as a competitor, with id 0, because no competitor has that id.
     */
    public static function fromInput(mixed $sides): self
    {
        if (! is_array($sides)) {
            return new self([]);
        }

        $entries = [];
        $side = 0;

        foreach ($sides as $chosen) {
            if (! is_array($chosen)) {
                continue;
            }

            foreach (['wrestlers' => Wrestler::class, 'tag_teams' => TagTeam::class] as $kind => $model) {
                if (! is_array($chosen[$kind] ?? null)) {
                    continue;
                }

                foreach ($chosen[$kind] as $id) {
                    $entries[] = [
                        'type' => Relation::getMorphAlias($model),
                        'id' => is_int($id) || is_string($id) ? (int) $id : 0,
                        'side' => $side,
                    ];
                }
            }

            $side++;
        }

        return new self($entries);
    }

    /**
     * Every selected competitor, in the order they were chosen.
     *
     * @return list<array{type: string, id: int, side: int}>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * The selected ids per competitor type (the morph alias), the types in the order they first appear.
     *
     * @return array<string, list<int>>
     */
    public function idsByType(): array
    {
        $ids = [];

        foreach ($this->entries as $entry) {
            $ids[$entry['type']][] = $entry['id'];
        }

        return $ids;
    }

    /**
     * Each side that has a selected competitor, once, in the order the sides first appear.
     *
     * @return list<int>
     */
    public function sides(): array
    {
        return array_values(array_unique(array_column($this->entries, 'side')));
    }

    /** The competitors selected on `$side`. */
    public function onSide(int $side): self
    {
        return new self(array_values(array_filter($this->entries, fn (array $entry): bool => $entry['side'] === $side)));
    }

    /** The competitors selected on every side but `$side`: the ones facing it. */
    public function offSide(int $side): self
    {
        return new self(array_values(array_filter($this->entries, fn (array $entry): bool => $entry['side'] !== $side)));
    }

    /** Whether exactly two competitors of the same type were chosen, one against the other. */
    public function isHeadToHead(): bool
    {
        if (count($this->entries) !== 2) {
            return false;
        }

        [$first, $second] = $this->entries;

        return $first['side'] !== $second['side'] && $first['type'] === $second['type'];
    }
}
