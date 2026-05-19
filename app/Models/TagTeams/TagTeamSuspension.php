<?php

declare(strict_types=1);

namespace App\Models\TagTeams;

use Database\Factories\TagTeams\TagTeamSuspensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
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
 *
 * @property-read TagTeam|null $tagTeam
 *
 * @method static \Database\Factories\TagTeams\TagTeamSuspensionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamSuspension query()
 *
 * @mixin \Eloquent
 */
#[Table('tag_teams_suspensions')]
#[Fillable('tag_team_id', 'started_at', 'ended_at')]
#[UseFactory(TagTeamSuspensionFactory::class)]
class TagTeamSuspension extends Model
{
    /** @use HasFactory<TagTeamSuspensionFactory> */
    use HasFactory;

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
