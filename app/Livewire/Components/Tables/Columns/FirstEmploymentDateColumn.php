<?php

declare(strict_types=1);

namespace App\Livewire\Components\Tables\Columns;

use App\Livewire\Table\Column;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

class FirstEmploymentDateColumn extends Column
{
    public function __construct(string $title, ?string $from = null)
    {
        parent::__construct($title, $from);
        $this->label(fn (Wrestler|TagTeam|Manager|Referee $row, Column $column): string => $row->getFormattedFirstEmployment());
    }
}
