<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Data\Titles\LongestReigningChampionSummary;
use App\Data\Titles\TitleData;
use App\Models\Contracts\HasActivityPeriods;
use App\Models\Contracts\Retirable;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

interface TitleRepositoryInterface
{
    // CRUD operations
    public function create(TitleData $titleData): Title;

    public function update(Title $title, TitleData $titleData): Title;

    public function delete(Title $title): void;

    public function restore(Title $title): void;

    // Activity operations
    public function createActivity(HasActivityPeriods $title, Carbon $startDate): void;

    public function endActivity(HasActivityPeriods $title, Carbon $endDate): void;

    // Retirement operations
    /**
     * @param  Retirable<Model, Model>  $title
     */
    public function createRetirement(Retirable $title, Carbon $startDate): void;

    /**
     * @param  Retirable<Model, Model>  $title
     */
    public function endRetirement(Retirable $title, Carbon $endDate): void;

    // Analytics operations
    public function getLongestReigningChampion(Title $title): ?LongestReigningChampionSummary;

    // Domain-specific activity operations
    public function createDebut(Title $title, Carbon $debutDate): void;
}
