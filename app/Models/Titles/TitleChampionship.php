<?php

declare(strict_types=1);

namespace App\Models\Titles;

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Titles\TitleChampionshipFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $title_id
 * @property int $event_match_id
 * @property int $champion_id
 * @property string $champion_type
 * @property int|null $won_event_match_id
 * @property int|null $lost_event_match_id
 * @property Carbon $won_at
 * @property Carbon|null $lost_at
 * @property-read EventMatch|null $wonEventMatch
 * @property-read EventMatch|null $lostEventMatch
 * @property-read Title|null $title
 * @property-read Wrestler|TagTeam $champion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static TitleChampionshipBuilder<static>|TitleChampionship current()
 * @method static \Database\Factories\Titles\TitleChampionshipFactory factory($count = null, $state = [])
 * @method static TitleChampionshipBuilder<static>|TitleChampionship latestLost()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship latestWon()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship newModelQuery()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship newQuery()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship previous()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship query()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship withReignLength()
 *
 * @mixin \Eloquent
 */
#[UseFactory(TitleChampionshipFactory::class)]
#[UseEloquentBuilder(TitleChampionshipBuilder::class)]
class TitleChampionship extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'titles_championships';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title_id',
        'champion_type',
        'champion_id',
        'won_event_match_id',
        'lost_event_match_id',
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
     * Retrieve the title of the title championship.
     *
     * @return BelongsTo<Title, $this>
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    /**
     * Retrieve the champion of the title championship.
     *
     * @return MorphTo<Model, $this>
     */
    public function champion(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'champion_type', 'champion_id');
    }

    /**
     * Retrieve the event match where the champion won the title.
     *
     * @return BelongsTo<EventMatch, $this>
     */
    public function wonEventMatch(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'won_event_match_id');
    }

    /**
     * Retrieve the event match where the champion lost the title.
     *
     * @return BelongsTo<EventMatch, $this>
     */
    public function lostEventMatch(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'lost_event_match_id');
    }

    /**
     * Retrieve the number of days for a title championship reign.
     */
    public function lengthInDays(): int
    {
        if ($this->won_at === null) {
            return 0;
        }

        /** @var Carbon $datetime */
        $datetime = $this->lost_at ?? now();

        return (int) ($this->won_at->diffInDays($datetime));
    }
}
