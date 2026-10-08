<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Enums\Roster\RosterMemberKind;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Backs the tag team and stable forms' searchable selects.
 *
 * Search offers every non-deleted record of the form's promotion (see BaseForm::formPromotionId()), so a global
 * administrator without an enforced promotion context never sees another promotion's roster. It is a convenience:
 * the forms' validation rules and the actions remain the authority on who may join.
 */
final class RosterMemberSearch
{
    public const int LIMIT = 20;

    /**
     * Search the roster by name.
     *
     * @return array<int, array{id: int|string, name: string}>
     */
    public function search(RosterMemberKind $kind, string $term, ?int $promotionId): array
    {
        $column = $this->nameColumn($kind);
        // Names never contain LIKE wildcards, so drop them rather than depend on database-specific escaping.
        $needle = mb_trim(str_replace(['%', '_', '\\'], '', $term));

        return $this->options(
            $this->query($kind)
                ->where('promotion_id', $promotionId)
                ->when($needle !== '', fn (Builder $builder): Builder => $builder->whereLike($column, "%{$needle}%", caseSensitive: false))
                ->orderBy($column)
                ->orderBy('id')
                ->limit(self::LIMIT),
            $column,
        );
    }

    /**
     * Resolve display names for ids that are already selected, trashed or not.
     *
     * @param  array<int, mixed>  $ids
     * @return array<int, array{id: int|string, name: string}>
     */
    public function labels(RosterMemberKind $kind, array $ids, ?int $promotionId): array
    {
        $ids = collect($ids)->filter(fn (mixed $id): bool => is_numeric($id))->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        $column = $this->nameColumn($kind);

        $query = match ($kind) {
            RosterMemberKind::Wrestlers => Wrestler::query()->withTrashed(),
            RosterMemberKind::TagTeams => TagTeam::query()->withTrashed(),
            RosterMemberKind::Managers => Manager::query()->withTrashed(),
        };

        return $this->options($query->where('promotion_id', $promotionId)->whereKey($ids)->orderBy($column)->orderBy('id'), $column);
    }

    /** @return Builder<covariant Model> */
    private function query(RosterMemberKind $kind): Builder
    {
        return match ($kind) {
            RosterMemberKind::Wrestlers => Wrestler::query(),
            RosterMemberKind::TagTeams => TagTeam::query(),
            RosterMemberKind::Managers => Manager::query(),
        };
    }

    private function nameColumn(RosterMemberKind $kind): string
    {
        return match ($kind) {
            RosterMemberKind::Managers => 'full_name',
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
