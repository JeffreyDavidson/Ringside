<?php

declare(strict_types=1);

namespace App\View\Columns;

use App\Livewire\Table\Column;
use App\Models\Roster\Stables\Stable;
use App\Models\Titles\Title;

class FirstActivationDateColumn extends Column
{
    public function __construct(string $title, ?string $from = null)
    {
        parent::__construct($title, $from);
        $this->label(fn (Stable|Title $row, Column $column): string => $row->firstActivityPeriod?->started_at?->format('Y-m-d') ?? 'TBD');
    }
}
