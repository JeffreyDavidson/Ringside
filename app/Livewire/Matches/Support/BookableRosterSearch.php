<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Support;

use App\Enums\Roster\BookableRosterKind;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Backs the match form's searchable selects.
 *
 * Search only ever offers bookable records, but it is a convenience: the IsBookable validation
 * rules and RosterBookingEligibility remain the authority on what can actually be booked.
 */
final class BookableRosterSearch
{
    public const int LIMIT = 20;

    /** @return array<int, array{id: int|string, name: string}> */
    public function search(BookableRosterKind $kind, string $term): array
    {
        $column = $this->nameColumn($kind);
        // Names never contain LIKE wildcards, so drop them rather than depend on database-specific escaping.
        $needle = mb_trim(str_replace(['%', '_', '\\'], '', $term));

        $query = match ($kind) {
            BookableRosterKind::Wrestlers => Wrestler::query()->bookable(),
            BookableRosterKind::TagTeams => TagTeam::query()->bookable(),
            BookableRosterKind::Referees => Referee::query()->bookable(),
        };

        return $this->options(
            $query
                ->when($needle !== '', fn (Builder $builder): Builder => $builder->whereLike($column, "%{$needle}%", caseSensitive: false))
                ->orderBy($column)
                ->orderBy('id')
                ->limit(self::LIMIT),
            $column,
        );
    }

    /**
     * Resolve display names for ids that are already selected, bookable or not, trashed or not.
     *
     * @param  array<int, mixed>  $ids
     * @return array<int, array{id: int|string, name: string}>
     */
    public function labels(BookableRosterKind $kind, array $ids): array
    {
        $ids = collect($ids)->filter(fn (mixed $id): bool => is_numeric($id))->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        $column = $this->nameColumn($kind);
        $query = match ($kind) {
            BookableRosterKind::Wrestlers => Wrestler::query()->withTrashed(),
            BookableRosterKind::TagTeams => TagTeam::query()->withTrashed(),
            BookableRosterKind::Referees => Referee::query()->withTrashed(),
        };

        return $this->options($query->whereKey($ids)->orderBy($column)->orderBy('id'), $column);
    }

    private function nameColumn(BookableRosterKind $kind): string
    {
        return match ($kind) {
            BookableRosterKind::Referees => 'full_name',
            default => 'name',
        };
    }

    /**
     * @param  Builder<covariant Model>  $query
     * @return array<int, array{id: int|string, name: string}>
     */
    private function options(Builder $query, string $column): array
    {
        return $query
            ->pluck($column, 'id')
            ->map(fn (mixed $name, int|string $id): array => [
                'id' => $id,
                'name' => is_string($name) ? $name : '',
            ])
            ->values()
            ->all();
    }
}
