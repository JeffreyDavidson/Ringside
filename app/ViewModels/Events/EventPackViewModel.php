<?php

declare(strict_types=1);

namespace App\ViewModels\Events;

use App\Models\Events\Event;
use App\Queries\Events\EventPackSourceQuery;
use Illuminate\Support\Facades\Gate;

/** @phpstan-import-type PackSource from EventPackSourceQuery */
class EventPackViewModel
{
    public function __construct(private readonly EventPackSourceQuery $query) {}

    /** @return PackSource */
    public function data(Event $event): array
    {
        $data = $this->query->get($event);

        if (Gate::denies('viewProduction', $event)) {
            unset($data['content']['production']);
            if ($data['pack'] !== null) {
                $data['pack'] = clone $data['pack'];
                $data['pack']->content = $data['content'];
            }
        }

        return $data;
    }
}
