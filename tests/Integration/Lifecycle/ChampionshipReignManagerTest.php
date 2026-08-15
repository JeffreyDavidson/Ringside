<?php

declare(strict_types=1);

use App\Lifecycle\ChampionshipReignManager;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\DB;

test('it ends only the current championship reign', function () {
    $title = Title::factory()->create();
    $previousReign = TitleChampionship::factory()
        ->for($title)
        ->ended()
        ->create();
    $currentReign = TitleChampionship::factory()
        ->for($title)
        ->current()
        ->create();
    $endedAt = now()->startOfSecond();

    DB::transaction(function () use ($title, $endedAt): void {
        resolve(ChampionshipReignManager::class)->endCurrentReign($title, $endedAt);
    });

    expect($currentReign->refresh()->lost_at)->toEqual($endedAt)
        ->and($previousReign->refresh()->lost_at)->not->toEqual($endedAt);
});

test('ending a vacant championship is a no-op', function () {
    $title = Title::factory()->create();

    DB::transaction(function () use ($title): void {
        resolve(ChampionshipReignManager::class)->endCurrentReign($title, now());
    });

    expect($title->championships()->doesntExist())->toBeTrue();
});
