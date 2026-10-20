<?php

declare(strict_types=1);

use App\Collections\TitleChampionshipCollection;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Carbon;

it('is the collection class of loaded title championships', function () {
    // Arrange
    TitleChampionship::factory()->create();

    // Act
    $reigns = TitleChampionship::query()->get();

    // Assert
    expect($reigns)->toBeInstanceOf(TitleChampionshipCollection::class);
});

it('keeps only the active reigns of one title', function () {
    // Arrange
    $title = Title::factory()->create();
    $deletedReign = TitleChampionship::factory()->for($title)->create();
    $deletedReign->delete();
    $activeReign = TitleChampionship::factory()->for($title)->create();
    TitleChampionship::factory()->create();
    $reigns = TitleChampionship::query()->withTrashed()->get();

    // Act
    $titleReigns = $reigns->forTitle($title);

    // Assert
    expect($titleReigns->pluck('id')->all())->toBe([$activeReign->id]);
});

it('drops a reign deleted after the collection was loaded each time it is asked', function () {
    // Arrange
    $title = Title::factory()->create();
    $reign = TitleChampionship::factory()->for($title)->create();
    $reigns = TitleChampionship::query()->withTrashed()->get();
    $beforeDelete = $reigns->forTitle($title)->count();

    // Act
    $reigns->firstOrFail()->delete();
    $afterDelete = $reigns->forTitle($title)->count();

    // Assert
    expect($beforeDelete)->toBe(1)
        ->and($afterDelete)->toBe(0)
        ->and($reign->refresh()->trashed())->toBeTrue();
});

it('finds the reign won or lost at a match', function () {
    // Arrange
    $wonMatch = EventMatch::factory()->create();
    $lostMatch = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    $reignWonAtMatch = TitleChampionship::factory()->create(['won_match_id' => $wonMatch->id]);
    $reignLostAtMatch = TitleChampionship::factory()->create(['lost_match_id' => $lostMatch->id]);
    $reigns = TitleChampionship::query()->get();

    // Act
    $won = $reigns->wonAt($wonMatch);
    $lost = $reigns->lostAt($lostMatch);

    // Assert
    expect($won?->is($reignWonAtMatch))->toBeTrue()
        ->and($lost?->is($reignLostAtMatch))->toBeTrue()
        ->and($reigns->wonAt($otherMatch))->toBeNull()
        ->and($reigns->lostAt($otherMatch))->toBeNull()
        ->and($reigns->wonAt($lostMatch))->toBeNull();
});

it('finds the most recently won reign that has not ended', function () {
    // Arrange
    TitleChampionship::factory()->create([
        'won_at' => Carbon::parse('2026-01-01'),
        'lost_at' => Carbon::parse('2026-02-01'),
    ]);
    TitleChampionship::factory()->create(['won_at' => Carbon::parse('2026-03-01')]);
    $latestOpenReign = TitleChampionship::factory()->create(['won_at' => Carbon::parse('2026-04-01')]);
    $reigns = TitleChampionship::query()->get();

    // Act
    $current = $reigns->current();

    // Assert
    expect($current?->is($latestOpenReign))->toBeTrue();
});

it('has no current reign when every reign has ended', function () {
    // Arrange
    TitleChampionship::factory()->create([
        'won_at' => Carbon::parse('2026-01-01'),
        'lost_at' => Carbon::parse('2026-02-01'),
    ]);

    // Act
    $current = TitleChampionship::query()->get()->current();

    // Assert
    expect($current)->toBeNull();
});

it('keeps the reigns won after a date except the one won at the given match', function () {
    // Arrange
    $date = Carbon::parse('2026-03-01 12:00:00');
    $match = EventMatch::factory()->create();
    $reignOnDate = TitleChampionship::factory()->create(['won_at' => $date]);
    $reignAfterDate = TitleChampionship::factory()->create(['won_at' => $date->copy()->addSecond()]);
    $reignBeforeDate = TitleChampionship::factory()->create(['won_at' => $date->copy()->subSecond()]);
    $reignWonAtMatch = TitleChampionship::factory()->create([
        'won_match_id' => $match->id,
        'won_at' => $date->copy()->addDay(),
    ]);
    $reigns = TitleChampionship::query()->get();

    // Act
    $later = $reigns->laterThan($date, $match);

    // Assert
    expect($later->pluck('id')->all())->toBe([$reignAfterDate->id])
        ->and($later->contains('id', $reignOnDate->id))->toBeFalse()
        ->and($later->contains('id', $reignBeforeDate->id))->toBeFalse()
        ->and($later->contains('id', $reignWonAtMatch->id))->toBeFalse();
});

describe('heldBefore', function () {
    it('finds a reign that began before the date and is still open', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $match = EventMatch::factory()->create();
        $reign = TitleChampionship::factory()->create(['won_at' => $date->copy()->subSecond()]);

        // Act
        $held = TitleChampionship::query()->get()->heldBefore($date, $match);

        // Assert
        expect($held?->is($reign))->toBeTrue();
    });

    it('excludes a reign that began on the date itself', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $match = EventMatch::factory()->create();
        TitleChampionship::factory()->create(['won_at' => $date]);

        // Act
        $held = TitleChampionship::query()->get()->heldBefore($date, $match);

        // Assert
        expect($held)->toBeNull();
    });

    it('excludes a reign that ended on the date itself', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $match = EventMatch::factory()->create();
        TitleChampionship::factory()->create([
            'won_at' => $date->copy()->subDay(),
            'lost_at' => $date,
        ]);

        // Act
        $held = TitleChampionship::query()->get()->heldBefore($date, $match);

        // Assert
        expect($held)->toBeNull();
    });

    it('includes a reign that ended just after the date', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $match = EventMatch::factory()->create();
        $reign = TitleChampionship::factory()->create([
            'won_at' => $date->copy()->subDay(),
            'lost_at' => $date->copy()->addSecond(),
        ]);

        // Act
        $held = TitleChampionship::query()->get()->heldBefore($date, $match);

        // Assert
        expect($held?->is($reign))->toBeTrue();
    });

    it('includes a reign the match itself ended even though it ended on the date', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $match = EventMatch::factory()->create();
        $reign = TitleChampionship::factory()->create([
            'won_at' => $date->copy()->subDay(),
            'lost_at' => $date,
            'lost_match_id' => $match->id,
        ]);

        // Act
        $held = TitleChampionship::query()->get()->heldBefore($date, $match);

        // Assert
        expect($held?->is($reign))->toBeTrue();
    });

    it('excludes the reign the match itself won', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $match = EventMatch::factory()->create();
        TitleChampionship::factory()->create([
            'won_at' => $date->copy()->subDay(),
            'won_match_id' => $match->id,
        ]);

        // Act
        $held = TitleChampionship::query()->get()->heldBefore($date, $match);

        // Assert
        expect($held)->toBeNull();
    });
});

describe('covering', function () {
    it('includes a reign that began on the date itself', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $reign = TitleChampionship::factory()->create(['won_at' => $date]);

        // Act
        $covering = TitleChampionship::query()->get()->covering($date);

        // Assert
        expect($covering?->is($reign))->toBeTrue();
    });

    it('excludes a reign that began just after the date', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        TitleChampionship::factory()->create(['won_at' => $date->copy()->addSecond()]);

        // Act
        $covering = TitleChampionship::query()->get()->covering($date);

        // Assert
        expect($covering)->toBeNull();
    });

    it('excludes a reign that ended on the date itself', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        TitleChampionship::factory()->create([
            'won_at' => $date->copy()->subDay(),
            'lost_at' => $date,
        ]);

        // Act
        $covering = TitleChampionship::query()->get()->covering($date);

        // Assert
        expect($covering)->toBeNull();
    });

    it('includes a reign that ended just after the date', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $reign = TitleChampionship::factory()->create([
            'won_at' => $date->copy()->subDay(),
            'lost_at' => $date->copy()->addSecond(),
        ]);

        // Act
        $covering = TitleChampionship::query()->get()->covering($date);

        // Assert
        expect($covering?->is($reign))->toBeTrue();
    });

    it('does not make an exception for the match that ended the reign', function () {
        // Arrange
        $date = Carbon::parse('2026-03-01 12:00:00');
        $match = EventMatch::factory()->create();
        TitleChampionship::factory()->create([
            'won_at' => $date->copy()->subDay(),
            'lost_at' => $date,
            'lost_match_id' => $match->id,
        ]);

        // Act
        $covering = TitleChampionship::query()->get()->covering($date);

        // Assert
        expect($covering)->toBeNull();
    });
});
