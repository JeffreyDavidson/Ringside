<?php

declare(strict_types=1);

namespace App\Livewire\Components\Tables\Columns;

use App\Livewire\Table\Column;
use App\Models\Stables\Stable;
use App\Models\Titles\Title;

class FirstActivityPeriodColumn extends Column
{
    public function __construct(string $title, ?string $from = null)
    {
        parent::__construct($title, $from);
        $this->label(fn (Stable|Title $row, Column $column): string => $row->getFormattedFirstActivity());
    }
}
