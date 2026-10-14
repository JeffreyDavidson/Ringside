<?php

declare(strict_types=1);

namespace App\Models\Tournaments;

use App\Models\Concerns\BelongsToPromotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Tournaments\TournamentEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $tournament_id
 * @property int $seed
 * @property string $competitor_type
 * @property int $competitor_id
 * @property bool $withdrawn
 * @property-read Tournament $tournament
 * @property-read Wrestler|TagTeam $competitor
 */
#[Fillable([])]
#[UseFactory(TournamentEntryFactory::class)]
class TournamentEntry extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<TournamentEntryFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['seed' => 'integer', 'withdrawn' => 'boolean'];
    }

    /** @return BelongsTo<Tournament, $this> */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /** @return MorphTo<Model, $this> */
    public function competitor(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
