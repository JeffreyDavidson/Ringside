<?php

declare(strict_types=1);

namespace App\Models\Tournaments;

use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentStatus;
use App\Models\Concerns\BelongsToPromotion;
use Database\Factories\Tournaments\TournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $promotion_id
 * @property string $name
 * @property ?string $description
 * @property EntrantKind $entrant_kind
 * @property int $capacity
 * @property bool $repeat_appearances
 * @property TournamentStatus $status
 * @property ?string $request_id
 * @property int $version
 * @property-read Collection<int, TournamentEntry> $entries
 * @property-read Collection<int, TournamentNode> $nodes
 * @property-read Collection<int, TournamentMatchLink> $links
 */
#[Fillable(['name', 'description', 'entrant_kind', 'capacity', 'repeat_appearances'])]
#[UseFactory(TournamentFactory::class)]
class Tournament extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    #[\Override]
    protected function casts(): array
    {
        return [
            'entrant_kind' => EntrantKind::class,
            'status' => TournamentStatus::class,
            'capacity' => 'integer',
            'repeat_appearances' => 'boolean',
            'version' => 'integer',
        ];
    }

    /** @return HasMany<TournamentEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(TournamentEntry::class)->orderBy('seed');
    }

    /** @return HasMany<TournamentNode, $this> */
    public function nodes(): HasMany
    {
        return $this->hasMany(TournamentNode::class)->orderBy('round')->orderBy('position');
    }

    /** @return HasMany<TournamentMatchLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(TournamentMatchLink::class);
    }
}
