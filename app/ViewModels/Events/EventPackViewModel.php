<?php

declare(strict_types=1);

namespace App\ViewModels\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Services\Events\EventPackSourceService;
use App\Services\Events\ProductionRedactionService;
use Illuminate\Support\Facades\Gate;

/** @phpstan-import-type PackSource from EventPackSourceService */
class EventPackViewModel
{
    public function __construct(private readonly EventPackSourceService $query, private readonly ProductionRedactionService $redaction) {}

    /** @return PackSource */
    public function data(Event $event): array
    {
        $data = $this->query->get($event);

        if (Gate::denies('viewProduction', $event)) {
            $data['content'] = $this->redaction->packWithoutProduction($data['content']);

            if ($data['pack'] instanceof EventPack) {
                $data['pack'] = clone $data['pack'];
                $data['pack']->content = $data['content'];
            }
        }

        return $data;
    }
}
