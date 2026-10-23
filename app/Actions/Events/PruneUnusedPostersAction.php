<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Queries\Events\ReferencedPosterKeysQuery;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

class PruneUnusedPostersAction
{
    public function __construct(private readonly ReferencedPosterKeysQuery $referenced) {}

    /**
     * Delete poster files no draft or released card names and that were written before $olderThan, so an upload
     * still being saved is never touched. A dry run deletes nothing. Returns the paths deleted (or that would be).
     *
     * @return list<string>
     */
    public function handle(CarbonInterface $olderThan, bool $dryRun): array
    {
        $disk = Storage::disk('local');
        $inUse = array_flip($this->referenced->get());
        $removed = [];

        foreach ($disk->files('event-posters') as $path) {
            if (isset($inUse[basename($path)]) || $disk->lastModified($path) >= $olderThan->getTimestamp()) {
                continue;
            }

            if (! $dryRun) {
                $disk->delete($path);
            }

            $removed[] = $path;
        }

        return $removed;
    }
}
