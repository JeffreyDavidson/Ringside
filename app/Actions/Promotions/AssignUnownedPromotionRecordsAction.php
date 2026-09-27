<?php

declare(strict_types=1);

namespace App\Actions\Promotions;

use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class AssignUnownedPromotionRecordsAction
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function handle(Promotion $promotion, string $modelClass, bool $dryRun): int
    {
        $unassignedCount = $modelClass::query()->whereNull('promotion_id')->count();

        if ($dryRun || $unassignedCount === 0) {
            return $unassignedCount;
        }

        $modelClass::query()
            ->whereNull('promotion_id')
            ->chunkById(200, function (Collection $records) use ($promotion): void {
                $records->each(function (Model $record) use ($promotion): void {
                    $record->forceFill(['promotion_id' => $promotion->getKey()])->save();
                });
            });

        return $unassignedCount;
    }
}
