<?php

declare(strict_types=1);

namespace App\View\Columns;

use App\Models\Stable;
use App\Models\Title;
use Rappasoft\LaravelLivewireTables\Views\Column;

final class FirstActivationDateColumn extends Column
{
    public function __construct(string $title, ?string $from = null)
    {
        parent::__construct($title, $from);
        $this->label(fn (Stable|Title $row, Column $column) => $row->getFormattedFirstActivation());
    }
}
