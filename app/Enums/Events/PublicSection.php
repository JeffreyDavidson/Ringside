<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum PublicSection: string
{
    case Card = 'card';
    case Results = 'results';
}
