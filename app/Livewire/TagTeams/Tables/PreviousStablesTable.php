<?php

declare(strict_types=1);

namespace App\Livewire\TagTeams\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Stables\Stable;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PreviousStablesTable extends BasePreviousStablesTable
{
    protected string $databaseTableName = 'stables';

    public ?int $tagTeamId;

    /**
     * @return Builder<Stable>
     */
    public function builder(): Builder
    {
        if (! isset($this->tagTeamId)) {
            throw new Exception("You didn't specify a tag team");
        }

        return Stable::query()
            ->join('stables_tag_teams', 'stables.id', '=', 'stables_tag_teams.stable_id')
            ->where('stables_tag_teams.tag_team_id', $this->tagTeamId)
            ->whereNotNull('stables_tag_teams.left_at')
            ->select('stables.*')
            ->addSelect([
                'joined_at' => DB::raw('stables_tag_teams.joined_at'),
                'left_at' => DB::raw('stables_tag_teams.left_at')
            ])
            ->orderByDesc('stables_tag_teams.joined_at');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setColumnSelectDisabled()
            ->setSearchPlaceholder('Search '.$this->resourceName)
            ->setPaginationEnabled()
            ->setPerPageAccepted([5, 10, 25, 50, 100])
            ->setLoadingPlaceholderContent('Loading')
            ->setLoadingPlaceholderEnabled();
    }
}
