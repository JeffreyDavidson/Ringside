<?php

declare(strict_types=1);

namespace App\Livewire\Components\Tables\Columns;

use App\Models\Stables\Stable;
use App\Models\Titles\Title;
use Rappasoft\LaravelLivewireTables\Views\Column;

class FirstActivityPeriodColumn extends Column
{
    public function __construct(string $title, ?string $from = null)
    {
        parent::__construct($title, $from);
        $this->label(fn (Stable|Title $row, Column $column): string => $row->getFormattedFirstActivity());
    }
}
