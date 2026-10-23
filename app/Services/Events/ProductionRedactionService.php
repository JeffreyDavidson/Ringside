<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;

/**
 * Removes the production data a viewer who may not see it must never receive: the production block from a saved
 * pack's content, and the production block, each item's cues and the crew rows of the roster from a live show's
 * content. The callers decide who may see production.
 *
 * @phpstan-import-type Content from EventPack as PackContent
 * @phpstan-import-type Content from EventLiveRun as LiveContent
 */
class ProductionRedactionService
{
    /**
     * @param  PackContent  $content
     * @return PackContent
     */
    public function packWithoutProduction(array $content): array
    {
        unset($content['production']);

        return $content;
    }

    /**
     * @param  LiveContent  $content
     * @return LiveContent
     */
    public function liveShowWithoutProduction(array $content): array
    {
        unset($content['production']);
        $content['items'] = array_map(function (array $item): array {
            unset($item['cues']);

            return $item;
        }, $content['items']);
        $content['roster'] = array_values(array_filter($content['roster'], fn (array $person): bool => ! str_starts_with($person['key'], 'crew:')));

        return $content;
    }
}
