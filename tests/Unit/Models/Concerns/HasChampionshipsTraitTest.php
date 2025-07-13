<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

describe('HasChampionships Trait Unit Tests', function () {
    beforeEach(function () {
        // Use the real Title model which implements the trait
        $this->model = Title::factory()->create();
    });

    describe('basic relationships', function () {
        test('championships relationship returns correct type', function () {
            $model = $this->model;
            expect($model->championships())->toBeInstanceOf(HasMany::class);
        });

        test('model can have championships', function () {
            $model = $this->model;
            $championship = TitleChampionship::factory()->create(['title_id' => $model->id]);

            expect($model->championships->pluck('id'))->toContain($championship->id);
        });

        test('currentChampionship relationship returns correct type', function () {
            $model = $this->model;
            expect($model->currentChampionship())->toBeInstanceOf(HasOne::class);
        });
    });

    describe('current championship methods', function () {
        test('model can have current championship', function () {
            $model = $this->model;
            $champion = Wrestler::factory()->create();
            $championship = TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subWeek(),
                'lost_at' => null,
            ]);

            $model->load('championships');
            expect($model->getCurrentChampionship())->not->toBeNull();
            expect($model->getCurrentChampionship()->lost_at)->toBeNull();
        });

        test('model without current championship returns null', function () {
            $model = $this->model;
            expect($model->getCurrentChampionship())->toBeNull();
        });

        test('currentChampion returns the champion model', function () {
            $model = $this->model;
            $champion = Wrestler::factory()->create();
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subWeek(),
                'lost_at' => null,
            ]);

            expect($model->currentChampion())->not->toBeNull();
            expect($model->currentChampion()->id)->toBe($champion->id);
        });

        test('currentChampion returns null when no current championship', function () {
            $model = $this->model;
            expect($model->currentChampion())->toBeNull();
        });
    });

    describe('previous championship methods', function () {
        test('previousChampionship returns most recent completed reign', function () {
            $model = $this->model;
            $champion1 = Wrestler::factory()->create();
            $champion2 = Wrestler::factory()->create();

            // Create past championship
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion1->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subYear(),
                'lost_at' => now()->subMonth(),
            ]);

            // Create current championship
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion2->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subWeek(),
                'lost_at' => null,
            ]);

            $previous = $model->previousChampionship();
            expect($previous)->not->toBeNull();
            expect($previous->champion_id)->toBe($champion1->id);
        });

        test('previousChampionship returns null when no previous reigns', function () {
            $model = $this->model;
            expect($model->previousChampionship())->toBeNull();
        });

        test('previousChampion returns the previous champion model', function () {
            $model = $this->model;
            $champion1 = Wrestler::factory()->create();
            $champion2 = Wrestler::factory()->create();

            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion1->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subYear(),
                'lost_at' => now()->subMonth(),
            ]);

            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion2->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subWeek(),
                'lost_at' => null,
            ]);

            expect($model->previousChampion())->not->toBeNull();
            expect($model->previousChampion()->id)->toBe($champion1->id);
        });

        test('previousChampion returns null when no previous reigns', function () {
            $model = $this->model;
            expect($model->previousChampion())->toBeNull();
        });
    });

    describe('first championship methods', function () {
        test('firstChampionship returns earliest reign', function () {
            $model = $this->model;
            $champion1 = Wrestler::factory()->create();
            $champion2 = Wrestler::factory()->create();

            // Create later championship
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion2->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subWeek(),
                'lost_at' => null,
            ]);

            // Create earlier championship
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion1->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subYear(),
                'lost_at' => now()->subMonth(),
            ]);

            $first = $model->firstChampionship();
            expect($first)->not->toBeNull();
            expect($first->champion_id)->toBe($champion1->id);
        });

        test('firstChampionship returns null when no championships', function () {
            $model = $this->model;
            expect($model->firstChampionship())->toBeNull();
        });

        test('firstChampion returns the first champion model', function () {
            $model = $this->model;
            $champion1 = Wrestler::factory()->create();
            $champion2 = Wrestler::factory()->create();

            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion2->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subWeek(),
                'lost_at' => null,
            ]);

            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion1->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subYear(),
                'lost_at' => now()->subMonth(),
            ]);

            expect($model->firstChampion())->not->toBeNull();
            expect($model->firstChampion()->id)->toBe($champion1->id);
        });

        test('firstChampion returns null when no championships', function () {
            $model = $this->model;
            expect($model->firstChampion())->toBeNull();
        });
    });

    describe('longest championship methods', function () {
        test('longestChampionship returns reign with longest duration', function () {
            $model = $this->model;
            $champion1 = Wrestler::factory()->create();
            $champion2 = Wrestler::factory()->create();

            // Create shorter reign
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion1->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subMonth(),
                'lost_at' => now()->subWeek(),
            ]);

            // Create longer reign
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion2->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subYear(),
                'lost_at' => now()->subMonth(),
            ]);

            $longest = $model->longestChampionship();
            expect($longest)->not->toBeNull();
            expect($longest->champion_id)->toBe($champion2->id);
        });

        test('longestChampionship returns null when no championships', function () {
            $model = $this->model;
            expect($model->longestChampionship())->toBeNull();
        });

        test('longestChampion returns the longest reigning champion model', function () {
            $model = $this->model;
            $champion1 = Wrestler::factory()->create();
            $champion2 = Wrestler::factory()->create();

            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion1->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subMonth(),
                'lost_at' => now()->subWeek(),
            ]);

            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion2->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subYear(),
                'lost_at' => now()->subMonth(),
            ]);

            expect($model->longestChampion())->not->toBeNull();
            expect($model->longestChampion()->id)->toBe($champion2->id);
        });

        test('longestChampion returns null when no championships', function () {
            $model = $this->model;
            expect($model->longestChampion())->toBeNull();
        });
    });

    describe('utility methods', function () {
        test('reignCount returns correct number of reigns', function () {
            $model = $this->model;
            expect($model->reignCount())->toBe(0);

            TitleChampionship::factory()->create(['title_id' => $model->id]);
            TitleChampionship::factory()->create(['title_id' => $model->id]);

            expect($model->reignCount())->toBe(2);
        });

        test('isVacant returns true when no current champion', function () {
            $model = $this->model;
            expect($model->isVacant())->toBeTrue();
        });

        test('isVacant returns false when has current champion', function () {
            $model = $this->model;
            $champion = Wrestler::factory()->create();
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion->id,
                'champion_type' => Wrestler::class,
                'won_at' => now()->subWeek(),
                'lost_at' => null,
            ]);

            expect($model->isVacant())->toBeFalse();
        });
    });

    describe('complex scenarios', function () {
        test('model with multiple championships handles current correctly', function () {
            $model = $this->model;

            // Create past championship
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'won_at' => now()->subYear(),
                'lost_at' => now()->subMonth(),
            ]);

            // Create current championship
            TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'won_at' => now()->subWeek(),
                'lost_at' => null,
            ]);

            $model->load('championships');

            expect($model->championships)->toHaveCount(2);
            expect($model->getCurrentChampionship())->not->toBeNull();
            expect($model->getCurrentChampionship()->lost_at)->toBeNull();
        });

        test('model can exist without championships', function () {
            $model = $this->model;
            expect($model->championships()->count())->toBe(0);
            expect($model->getCurrentChampionship())->toBeNull();
        });

        test('model maintains relationship integrity when championships are deleted', function () {
            $model = $this->model;
            $champion = Wrestler::factory()->create();
            $championship = TitleChampionship::factory()->create([
                'title_id' => $model->id,
                'champion_id' => $champion->id,
                'champion_type' => Wrestler::class,
            ]);
            $model->load('championships');

            expect($model->championships->pluck('id'))->toContain($championship->id);

            $championship->delete();
            $model->refresh();

            expect($model->championships()->count())->toBe(0);
        });

        test('model can be associated with new championships after creation', function () {
            $model = $this->model;
            expect($model->championships)->toBeEmpty();

            $championship = TitleChampionship::factory()->create(['title_id' => $model->id]);
            $model->refresh();

            expect($model->championships->pluck('id'))->toContain($championship->id);
        });
    });
});
