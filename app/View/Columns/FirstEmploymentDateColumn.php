<?php

declare(strict_types=1);

namespace App\View\Columns;

use App\Livewire\Table\Column;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

class FirstEmploymentDateColumn extends Column
{
    public function __construct(string $title, ?string $from = null)
    {
        parent::__construct($title, $from);
        $this->label(fn (Wrestler|TagTeam|Manager|Referee $row, Column $column): string => $row->firstEmployment?->started_at?->format('Y-m-d') ?? 'TBD');
    }
}
