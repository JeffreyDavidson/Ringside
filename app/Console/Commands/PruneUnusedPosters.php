<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Events\PruneUnusedPostersAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('events:prune-posters {--dry-run : List the files that would be deleted and delete nothing}')]
#[Description('Delete event poster files no draft or released card uses and that are over a day old')]
class PruneUnusedPosters extends Command
{
    public function handle(PruneUnusedPostersAction $prune): int
    {
        $removed = $prune->handle(now()->subDay(), (bool) $this->option('dry-run'));

        if ($this->option('dry-run')) {
            array_map($this->line(...), $removed);
            $this->info(sprintf('Dry run: %d unused poster file(s) would be deleted.', count($removed)));

            return self::SUCCESS;
        }

        Log::info('Pruned unused event posters.', ['count' => count($removed)]);
        $this->info(sprintf('Deleted %d unused poster file(s).', count($removed)));

        return self::SUCCESS;
    }
}
