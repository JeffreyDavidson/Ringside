<?php

declare(strict_types=1);

namespace App\Models\Titles;

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Titles\TitleChampionshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $title_id
 * @property int $champion_id
 * @property string $champion_type
 * @property int|null $won_match_id
 * @property int|null $lost_match_id
 * @property int|null $previous_championship_id
 * @property Carbon $won_at
 * @property Carbon|null $lost_at
 * @property Carbon|null $deleted_at
 * @property-read Carbon $local_won_at
 * @property-read Carbon|null $local_lost_at
 * @property-read EventMatch|null $wonEventMatch
 * @property-read EventMatch|null $lostEventMatch
 * @property-read Title|null $title
 * @property-read TitleChampionship|null $previousChampionship
 * @property-read Wrestler|TagTeam $champion
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\Titles\TitleChampionshipFactory factory($count = null, $state = [])
 * @method static TitleChampionshipBuilder<static>|TitleChampionship current()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship forPreviousHistory()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship forChampion(Wrestler|TagTeam $champion)
 * @method static TitleChampionshipBuilder<static>|TitleChampionship forTagTeamId(int $tagTeamId)
 * @method static TitleChampionshipBuilder<static>|TitleChampionship forTitleId(int $titleId)
 * @method static TitleChampionshipBuilder<static>|TitleChampionship forWrestlerId(int $wrestlerId)
 * @method static TitleChampionshipBuilder<static>|TitleChampionship mostRecentlyLostFirst()
 * @method static TitleChampionshipBuilder<static>|TitleChampionship previous()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleChampionship withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Table('titles_championships')]
#[Fillable('title_id', 'champion_type', 'champion_id', 'won_match_id', 'lost_match_id', 'won_at', 'lost_at')]
#[UseEloquentBuilder(TitleChampionshipBuilder::class)]
#[UseFactory(TitleChampionshipFactory::class)]
class TitleChampionship extends Model
{
    /** @use HasFactory<TitleChampionshipFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
        ];
    }

    /**
     * Get when the reign began, as wall-clock time in the title's promotion time zone.
     *
     * @return Attribute<Carbon, never>
     */
    protected function localWonAt(): Attribute
    {
        return Attribute::make(
            get: fn (): Carbon => Promotion::toLocalTime($this->title?->promotion, $this->won_at)
        );
    }

    /**
     * Get when the reign ended, as wall-clock time in the title's promotion time zone.
     *
     * @return Attribute<Carbon|null, never>
     */
    protected function localLostAt(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Carbon => $this->lost_at instanceof Carbon
                ? Promotion::toLocalTime($this->title?->promotion, $this->lost_at)
                : null
        );
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
        return $this->morphTo(__FUNCTION__, 'champion_type', 'champion_id')->withTrashed();
    }

    /**
     * Retrieve the event match where the champion won the title.
     *
     * @return BelongsTo<EventMatch, $this>
     */
    public function wonEventMatch(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'won_match_id');
    }

    /**
     * Retrieve the event match where the champion lost the title.
     *
     * @return BelongsTo<EventMatch, $this>
     */
    public function lostEventMatch(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'lost_match_id');
    }

    /** @return BelongsTo<TitleChampionship, $this> */
    public function previousChampionship(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_championship_id');
    }
}
