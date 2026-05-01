<?php

declare(strict_types=1);

namespace App\Models\TagTeams;

use Database\Factories\TagTeams\TagTeamEmploymentFactory;
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
 * @method static \Database\Factories\TagTeams\TagTeamEmploymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamEmployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamEmployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagTeamEmployment query()
 *
 * @mixin \Eloquent
 */
#[Table('tag_teams_employments')]
#[Fillable('tag_team_id', 'started_at', 'ended_at')]
#[UseFactory(TagTeamEmploymentFactory::class)]
class TagTeamEmployment extends Model
{
    /** @use HasFactory<TagTeamEmploymentFactory> */
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
     * Get the referee associated with this employment.
     *
     * @return BelongsTo<TagTeam, $this>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }

    /**
     * Undocumented function
     */
    public function startedBefore(Carbon $employmentDate): bool
    {
        return $this->started_at->lte($employmentDate);
    }
}
