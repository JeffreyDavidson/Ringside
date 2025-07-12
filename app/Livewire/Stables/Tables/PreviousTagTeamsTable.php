<?php

declare(strict_types=1);

namespace App\Livewire\Stables\Tables;

use App\Livewire\Base\Tables\BasePreviousTagTeamsTable;
use App\Models\StableTagTeam;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousTagTeamsTable extends BasePreviousTagTeamsTable
{
    protected string $databaseTableName = 'stables_tag_teams';

    public ?int $stableId;

    /**
     * @return Builder<StableTagTeam>
     */
    public function builder(): Builder
    {
        if (! isset($this->stableId)) {
            throw new Exception("You didn't specify a stable");
        }

        return StableTagTeam::query()
            ->with(['tagTeam'])
            ->where('stable_id', $this->stableId)
            ->whereNotNull('left_at')
            ->orderByDesc('joined_at');
    }

    public function configure(): void
    {
        $this->addAdditionalSelects([
            'stables_tag_teams.tag_team_id as tag_team_id',
        ]);
    }
}
