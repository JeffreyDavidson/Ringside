<?php

declare(strict_types=1);

namespace App\Models\TagTeams;

use App\Builders\Roster\ManagerAssignmentBuilder;
use App\Models\Managers\Manager;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tag_team_id
 * @property int $manager_id
 * @property Carbon $hired_at
 * @property Carbon|null $fired_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Manager|null $manager
 * @property-read TagTeam|null $tagTeam
 *
 * @method static ManagerAssignmentBuilder<static> current()
 * @method static ManagerAssignmentBuilder<static> ended()
 * @method static ManagerAssignmentBuilder<static> newModelQuery()
 * @method static ManagerAssignmentBuilder<static> newQuery()
 * @method static ManagerAssignmentBuilder<static> query()
 *
 * @mixin \Eloquent
 */
#[Fillable('tag_team_id', 'manager_id', 'hired_at', 'fired_at')]
#[UseEloquentBuilder(ManagerAssignmentBuilder::class)]
class TagTeamManager extends Pivot
{
    protected $table = 'tag_teams_managers';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hired_at' => 'datetime',
            'fired_at' => 'datetime',
        ];
    }

    /**
     * Get the manager associated with this tag team.
     *
     * @return BelongsTo<Manager, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    /**
     * Get the tag team associated with this manager.
     *
     * @return BelongsTo<TagTeam, $this>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }
}
