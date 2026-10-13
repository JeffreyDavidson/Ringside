<?php

declare(strict_types=1);

namespace App\Models\Storylines;

use App\Models\Concerns\BelongsToPromotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Storylines\StorylineParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $storyline_id
 * @property int $promotion_id
 * @property string $participant_type
 * @property int $participant_id
 * @property string $label
 * @property ?string $role
 * @property Carbon $joined_on
 * @property ?Carbon $left_on
 * @property-read Wrestler|TagTeam|null $participant
 * @property-read Storyline $storyline
 */
#[Fillable([])]
#[UseFactory(StorylineParticipantFactory::class)]
class StorylineParticipant extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<StorylineParticipantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['joined_on' => 'date', 'left_on' => 'date'];
    }

    /** @return BelongsTo<Storyline, $this> */
    public function storyline(): BelongsTo
    {
        return $this->belongsTo(Storyline::class);
    }

    /** @return MorphTo<Model, $this> */
    public function participant(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
