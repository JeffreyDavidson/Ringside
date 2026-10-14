<?php

declare(strict_types=1);

namespace App\Queries\Storylines;

use App\Enums\Roster\BookableRosterKind;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Builder;

class CreativeRosterSearch
{
    /** @return list<array{id: int|string, name: string}> */
    public function search(BookableRosterKind $kind, string $term, int $promotionId): array
    {
        $query = match ($kind) {
            BookableRosterKind::Wrestlers => Wrestler::query(),
            BookableRosterKind::TagTeams => TagTeam::query(),
            default => throw new \InvalidArgumentException('Creative participants must be wrestlers or tag teams.'),
        };
        $needle = mb_trim(str_replace(['%', '_', '\\'], '', $term));

        return array_values($query->where('promotion_id', $promotionId)
            ->when($needle !== '', fn (Builder $builder) => $builder->whereLike('name', "%{$needle}%"))
            ->orderBy('name')->orderBy('id')->limit(20)->pluck('name', 'id')
            ->map(fn (mixed $name, int|string $id): array => ['id' => $id, 'name' => is_string($name) ? $name : ''])->values()->all());
    }
}
