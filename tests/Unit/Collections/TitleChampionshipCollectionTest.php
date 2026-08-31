<?php

declare(strict_types=1);

use App\Collections\TitleChampionshipCollection;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Carbon;

it('filters championship reigns by title and active history', function () {
    $activeReign = new TitleChampionship([
        'title_id' => 1,
        'deleted_at' => null,
    ]);
    $deletedReign = new TitleChampionship(['title_id' => 1]);
    $deletedReign->setAttribute('deleted_at', Carbon::now());
    $otherTitleReign = new TitleChampionship([
        'title_id' => 2,
        'deleted_at' => null,
    ]);

    $reigns = new TitleChampionshipCollection([$activeReign, $deletedReign, $otherTitleReign]);

    expect($reigns->forTitleId(1)->active()->all())->toBe([$activeReign]);
});
