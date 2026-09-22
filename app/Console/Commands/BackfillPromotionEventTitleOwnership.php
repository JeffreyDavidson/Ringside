<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Titles\Title;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

#[Signature('promotions:backfill-event-title-ownership {promotion : The promotion ID that should own unassigned events and titles} {--force : Apply the ownership updates} {--dry-run : Report the records that would be updated}')]
#[Description('Backfill unassigned events and titles to an existing promotion')]
class BackfillPromotionEventTitleOwnership extends Command
{
    /**
     * @var array<class-string<Model>>
     */
    private const array PROMOTION_OWNED_MODELS = [
        Event::class,
        Title::class,
    ];

    public function handle(): int
    {
        $promotion = Promotion::query()->find($this->argument('promotion'));

        if ($promotion === null) {
            $this->error('The selected promotion does not exist.');

            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');

        if (! $isDryRun && ! $this->option('force')) {
            $this->error('Pass --force to apply the backfill, or use --dry-run to preview it.');

            return self::FAILURE;
        }

        $total = 0;

        foreach (self::PROMOTION_OWNED_MODELS as $modelClass) {
            $count = $modelClass::query()->whereNull('promotion_id')->count();
            $total += $count;

            $this->line(sprintf('%s: %d unassigned record(s)', class_basename($modelClass), $count));

            if (! $isDryRun && $count > 0) {
                $modelClass::query()
                    ->whereNull('promotion_id')
                    ->chunkById(200, function (Collection $records) use ($promotion): void {
                        $records->each(function (Model $record) use ($promotion): void {
                            $record->forceFill(['promotion_id' => $promotion->getKey()])->save();
                        });
                    });
            }
        }

        $this->info($isDryRun
            ? sprintf('Dry run complete. %d record(s) would be assigned.', $total)
            : sprintf('Backfill complete. %d record(s) assigned to %s.', $total, $promotion->name));

        return self::SUCCESS;
    }
}
