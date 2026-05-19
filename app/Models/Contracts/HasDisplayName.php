<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface HasDisplayName
{
    /**
     * Get the display-friendly name of the model.
     */
    public function getDisplayName(): string;
}
