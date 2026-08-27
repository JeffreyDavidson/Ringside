<?php

declare(strict_types=1);

namespace App\Services\Titles;

use App\Actions\Titles\DebutAction;
use App\Actions\Titles\ReinstateAction;
use App\Actions\Titles\UnretireAction;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class TitleActivationService
{
    public function __construct(
        private readonly DebutAction $debut,
        private readonly ReinstateAction $reinstate,
        private readonly UnretireAction $unretire,
    ) {}

    public function activate(Title $title, Carbon $activationDate): void
    {
        DB::transaction(function () use ($title, $activationDate): void {
            $lockedTitle = Title::query()->whereKey($title->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedTitle->isRetired()) {
                $this->unretire->handle($lockedTitle, $activationDate);
            }

            if ($lockedTitle->hasActivityPeriods()) {
                $this->reinstate->handle($lockedTitle, $activationDate);

                return;
            }

            $this->debut->handle($lockedTitle, $activationDate);
        });
    }
}
