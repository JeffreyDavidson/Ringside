<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tag_team_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read TFactory|null $use_factory
 * @property-read TagTeam|null $tagTeam
 *
 * @method static \Database\Factories\TagTeamSuspensionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension query()
 *
 * @mixin \Eloquent
 */
final class TagTeamSuspension extends Model
{
    /** @use HasFactory<\Database\Factories\TagTeamSuspensionFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tag_teams_suspensions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tag_team_id',
        'started_at',
        'ended_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TagTeam, $this>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }
}
