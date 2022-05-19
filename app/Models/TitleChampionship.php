<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TitleChampionship extends Model
{
    use HasFactory;

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
     * @var array
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
     * Undocumented function.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function allTitleChampions()
    {
        return $this->mergedRelationWithModel('all_title_champions');
    }

    /**
     * Undocumented function.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function champion()
    {
        return $this->morphTo();
    }
}
