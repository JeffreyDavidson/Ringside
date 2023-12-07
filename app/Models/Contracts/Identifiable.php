<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface Identifiable
{
    public function getIdentifier(): string;
}
