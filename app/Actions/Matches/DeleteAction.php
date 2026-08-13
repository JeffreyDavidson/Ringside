<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Models\Matches\EventMatch;

class DeleteAction
{
    public function handle(EventMatch $eventMatch): void
    {
        $eventMatch->delete();
    }
}
