<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $title_id
 * @property int $event_match_id
 * @property int $new_champion_id
 * @property string $new_champion_type
 * @property int $former_champion_id
 * @property string $formerchampion_type
 * @property \Illuminate\Support\Carbon $won_at
 * @property \Illuminate\Support\Carbon|null $lost_at
 * @property-read \Illuminate\Database\Eloquent\Model $currentChampion
 * @property-read \Illuminate\Database\Eloquent\Model $previousChampion
 * @property-read \App\Models\EventMatch|null $eventMatch
 * @property-read \App\Models\TFactory|null $use_factory
 * @property-read \App\Models\Title|null $title
 *
 * @method static \Database\Factories\TitleChampionshipFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship query()
 *
 * @mixin \Eloquent
 */
class TitleChampionship extends Model
{
    /** @use HasFactory<\Database\Factories\TitleChampionshipFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'title_championships';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title_id',
        'event_match_id',
        'champion_id',
        'champion_type',
        'won_at',
        'lost_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
            'last_held_reign' => 'datetime',
        ];
    }

    /**
     * Retrieve the title of the championship.
     *
     * @return BelongsTo<Title, $this>
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    /**
     * Retrieve the current champion of the title championship.
     *
     * @return MorphTo<Model, $this>
     */
    public function currentChampion(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'new_champion_type', 'new_champion_id');
    }

    /**
     * Retrieve the current champion of the title championship.
     *
     * @return MorphTo<Model, $this>
     */
    public function previousChampion(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'previous_champion_type', 'previous_champion_id');
    }

    /**
     * Retrieve the event match where the title championship switched hands.
     *
     * @return BelongsTo<EventMatch, $this>
     */
    public function eventMatch(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class);
    }

    /**
     * Retrieve the number of days for a title championship.
     */
    public function lengthInDays(): int
    {
        /** @var Carbon $datetime */
        $datetime = $this->lost_at ?? now();

        return intval($this->won_at->diffInDays($datetime));
    }

    public function wrestlers(): void {}
}
