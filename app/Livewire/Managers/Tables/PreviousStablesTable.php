<?php

declare(strict_types=1);

namespace App\Livewire\Managers\Tables;

use App\Livewire\Base\Tables\BasePreviousStablesTable;
use App\Models\Stables\Stable;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class PreviousStablesTable extends BasePreviousStablesTable
{
    /**
     * ManagerId to use for component.
     */
    public ?int $managerId;

    protected string $databaseTableName = 'stables';

    protected string $resourceName = 'stables';

    /**
     * Get stables that the manager was associated with through wrestlers/tag teams they managed.
     *
     * @return Builder<Stable>
     */
    public function builder(): Builder
    {
        if (! isset($this->managerId)) {
            throw new Exception("You didn't specify a manager");
        }

        // Get stables through wrestlers that this manager managed
        $wrestlerStables = Stable::query()
            ->join('stables_members as sm_w', 'stables.id', '=', 'sm_w.stable_id')
            ->join('wrestler_managers as wm', 'sm_w.member_id', '=', 'wm.wrestler_id')
            ->where('sm_w.member_type', 'wrestler')
            ->where('wm.manager_id', $this->managerId)
            ->whereNotNull('wm.ended_at')
            ->select('stables.*', 'wm.started_at as joined_at', 'wm.ended_at as left_at');

        // Get stables through tag teams that this manager managed
        $tagTeamStables = Stable::query()
            ->join('stables_members as sm_t', 'stables.id', '=', 'sm_t.stable_id')
            ->join('tag_team_managers as ttm', 'sm_t.member_id', '=', 'ttm.tag_team_id')
            ->where('sm_t.member_type', 'tagTeam')
            ->where('ttm.manager_id', $this->managerId)
            ->whereNotNull('ttm.ended_at')
            ->select('stables.*', 'ttm.started_at as joined_at', 'ttm.ended_at as left_at');

        // Union the results and return unique stables
        return $wrestlerStables->union($tagTeamStables)->distinct();
    }

    public function configure(): void
    {
        // No additional selects needed since we're selecting from stables directly
    }
}
