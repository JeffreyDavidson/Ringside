<?php

declare(strict_types=1);

namespace App\Models\TagTeams;

use Database\Factories\TagTeams\TagTeamSuspensionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tag_team_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TagTeam|null $tagTeam
 *
 * @method static \Database\Factories\TagTeams\TagTeamSuspensionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(TagTeamSuspensionFactory::class)]
class TagTeamSuspension extends Model
{
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
     * Get the tag team associated with this suspension.
     *
     * @return BelongsTo<TagTeam, $this>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }
}
