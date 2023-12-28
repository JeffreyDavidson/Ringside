<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation;

class TitleChampionship extends Model
{
    use HasFactory;
    use HasMergedRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'title_championships';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'last_held_reign' => 'datetime',
    ];

    /**
     * Retrieve the title of the championship.
     *
     * @return BelongsTo<Title, TitleChampionship>
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    /**
     * Retrieve all title champions for championships.
     */
    public function allTitleChampions(): MergedRelation
    {
        return $this->mergedRelation('all_title_champions');
    }

    /**
     * Retrieve the current champion of the title championship.
     *
     * @return MorphTo<Model, TitleChampionship>
     */
    public function currentChampion(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'champion_type', 'champion_id');
    }

    /**
     * Retrieve the previous champion of the title championship.
     *
     * @return MorphTo<Model, TitleChampionship>
     */
    public function previousChampion(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'champion_type', 'champion_id');
    }

    /**
     * Retrieve the event match where the title championship switched hands.
     *
     * @return BelongsTo<EventMatch, TitleChampionship>
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
        $datetime = $this->lost_at ?? now();

        return $this->won_at->diffInDays($datetime);
    }
}
