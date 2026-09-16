<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Managers\AssignManagersAction;
use App\Data\Wrestlers\WrestlerData;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function __construct(
        protected EmployAction $employAction,
        protected AssignManagersAction $assignManagersAction
    ) {}

    public function handle(WrestlerData $wrestlerData): Wrestler
    {
        return DB::transaction(function () use ($wrestlerData): Wrestler {
            $wrestler = Wrestler::query()->create([
                'name' => $wrestlerData->name,
                'height' => $wrestlerData->height->toInches(),
                'weight' => $wrestlerData->weight->toPounds(),
                'hometown' => $wrestlerData->hometown,
                'signature_move' => $wrestlerData->signature_move,
            ]);

            if ($wrestlerData->hasManagers()) {
                $this->assignManagersAction->handle(
                    $wrestler,
                    $wrestlerData->managers,
                    $wrestlerData->employment_date ?? now(),
                );
            }

            if (isset($wrestlerData->employment_date)) {
                $this->employAction->handle($wrestler, $wrestlerData->employment_date);
            }

            return $wrestler;
        });
    }
}
