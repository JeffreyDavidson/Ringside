<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Collections\TagTeamCollection;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The sides of a match being booked: for each side number, the wrestlers and the tag teams standing on it. It is a
 * read-side value with no column: it validates the shape when built and answers the questions the booking checks
 * and locks ask, so callers no longer unpack `$side['wrestlers'] ?? []` themselves.
 *
 * Side numbers (the collection keys) are kept as given. Every side is normalised to hold both lists, empty when a
 * side was given without one.
 */
final readonly class MatchSides
{
    /** @var Collection<int, array{wrestlers: list<Wrestler>, tag_teams: list<TagTeam>}> */
    private Collection $sides;

    /**
     * @param  Collection<int, covariant array<string, mixed>>  $sides  Each side holds optional `wrestlers` and `tag_teams` lists.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Collection $sides)
    {
        $this->sides = $sides->map(function (array $side): array {
            if (array_diff(array_keys($side), ['wrestlers', 'tag_teams']) !== []) {
                throw new InvalidArgumentException('A match side holds only wrestlers and tag_teams.');
            }

            return [
                'wrestlers' => $this->members($side['wrestlers'] ?? [], Wrestler::class, 'Every wrestler on a side must be a wrestler.'),
                'tag_teams' => $this->members($side['tag_teams'] ?? [], TagTeam::class, 'Every tag team on a side must be a tag team.'),
            ];
        });
    }

    /**
     * @template TMember of object
     *
     * @param  class-string<TMember>  $class
     * @return list<TMember>
     *
     * @throws InvalidArgumentException
     */
    private function members(mixed $members, string $class, string $message): array
    {
        $checked = [];

        foreach ((array) $members as $member) {
            if (! $member instanceof $class) {
                throw new InvalidArgumentException($message);
            }

            $checked[] = $member;
        }

        return $checked;
    }

    /** Whether no side was given at all. */
    public function isEmpty(): bool
    {
        return $this->sides->isEmpty();
    }

    /** The number of sides, empty ones included. */
    public function count(): int
    {
        return $this->sides->count();
    }

    /**
     * Every side by its side number, for code that works one side at a time.
     *
     * @return Collection<int, array{wrestlers: list<Wrestler>, tag_teams: list<TagTeam>}>
     */
    public function sides(): Collection
    {
        return $this->sides;
    }

    /** Only the sides with a wrestler or a tag team on them. */
    public function populated(): self
    {
        return new self($this->sides->filter(
            fn (array $side): bool => $side['wrestlers'] !== [] || $side['tag_teams'] !== []
        ));
    }

    /**
     * Every wrestler and tag team, side by side: a side's wrestlers first, then its tag teams.
     *
     * @return Collection<int, Wrestler|TagTeam>
     */
    public function competitors(): Collection
    {
        return $this->sides
            ->flatMap(fn (array $side): array => [...$side['wrestlers'], ...$side['tag_teams']])
            ->values();
    }

    /** @return Collection<int, Wrestler> */
    public function wrestlers(): Collection
    {
        return $this->sides
            ->flatMap(fn (array $side): array => $side['wrestlers'])
            ->values();
    }

    public function tagTeams(): TagTeamCollection
    {
        return new TagTeamCollection($this->sides->flatMap(fn (array $side): array => $side['tag_teams'])->values()->all());
    }

    /**
     * How many wrestlers and tag teams stand on each populated side, smallest first.
     *
     * @return list<int>
     */
    public function entriesPerSide(): array
    {
        return array_values($this->populated()->sides
            ->map(fn (array $side): int => count($side['wrestlers']) + count($side['tag_teams']))
            ->sort()
            ->all());
    }

    /**
     * How many roster members stand on each populated side, smallest first: a side's wrestlers plus the current
     * wrestlers of its tag teams, which must already be loaded.
     *
     * @return list<int>
     */
    public function rosterMembersPerSide(): array
    {
        return array_values($this->populated()->sides
            ->map(fn (array $side): int => count($side['wrestlers']) + new TagTeamCollection($side['tag_teams'])->currentWrestlers()->count())
            ->sort()
            ->all());
    }
}
