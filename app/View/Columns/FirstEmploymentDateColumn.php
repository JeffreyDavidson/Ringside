<?php

declare(strict_types=1);

namespace App\View\Columns;

use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Rappasoft\LaravelLivewireTables\Views\Column;

class FirstEmploymentDateColumn extends Column
{
    public function __construct(string $title, ?string $from = null)
    {
        parent::__construct($title, $from);
        $this->label(fn (Wrestler|TagTeam|Manager|Referee $row, Column $column): string => $row->getFormattedFirstEmployment());
    }
}
