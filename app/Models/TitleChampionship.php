<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

class TitleChampionship extends Model
{
    use HasFactory;
    use HasMergedRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['title_id', 'event_match_id', 'champion_id', 'champion_type', 'won_at', 'lost_at'];

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
    ];

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    /**
     * Retrieve all title champions for championships.
     *
     * @return \Staudenmeir\LaravelMergedRelations\Eloquent\Relations\MergedRelation
     */
    public function allTitleChampions()
    {
        return $this->mergedRelation('all_title_champions');
    }

    /**
     * Retrieve the champion of title championship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function champion()
    {
        return $this->morphTo();
    }
}
