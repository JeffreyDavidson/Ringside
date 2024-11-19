<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TagTeamPartner extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tag_teams_wrestlers';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TagTean, TagTeamPartner>
     */
    public function tagTeam(): BelongsTo
    {
        return $this->belongsTo(TagTeam::class);
    }

    /**
     * @return BelongsTo<Wrestler, TagTeamPartner>
     */
    public function wrestler(): BelongsTo
    {
        return $this->belongsTo(Wrestler::class);
    }
}
