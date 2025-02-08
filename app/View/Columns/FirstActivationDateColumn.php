<?php

declare(strict_types=1);

namespace App\View\Columns;

use Rappasoft\LaravelLivewireTables\Views\Column;

class FirstActivationDateColumn extends Column
{
    public function __construct(string $title, ?string $from = null)
    {
        parent::__construct($title, $from);
        $this->label(fn ($row, Column $column) => $row->getFormattedFirstAttribute());
    }
}
