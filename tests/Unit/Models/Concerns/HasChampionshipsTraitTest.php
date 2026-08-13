<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use App\Queries\Titles\TitleChampionshipQuery;
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
            expect(TitleChampionshipQuery::currentChampionship($model))->not->toBeNull();
            expect(requiredModel(TitleChampionshipQuery::currentChampionship($model))->lost_at)->toBeNull();
        });

        test('model without current championship returns null', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::currentChampionship($model))->toBeNull();
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

            expect(TitleChampionshipQuery::currentChampion($model))->not->toBeNull();
            expect(requiredModel(TitleChampionshipQuery::currentChampion($model))->getKey())->toBe($champion->id);
        });

        test('currentChampion returns null when no current championship', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::currentChampion($model))->toBeNull();
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

            $previous = TitleChampionshipQuery::previousChampionship($model);
            expect($previous)
                ->not->toBeNull()
                ->champion_id->toBe($champion1->id);
        });

        test('previousChampionship returns null when no previous reigns', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::previousChampionship($model))->toBeNull();
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

            expect(TitleChampionshipQuery::previousChampion($model))->not->toBeNull();
            expect(requiredModel(TitleChampionshipQuery::previousChampion($model))->getKey())->toBe($champion1->id);
        });

        test('previousChampion returns null when no previous reigns', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::previousChampion($model))->toBeNull();
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

            $first = TitleChampionshipQuery::firstChampionship($model);
            expect($first)
                ->not->toBeNull()
                ->champion_id->toBe($champion1->id);
        });

        test('firstChampionship returns null when no championships', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::firstChampionship($model))->toBeNull();
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

            expect(requiredModel(TitleChampionshipQuery::firstChampion($model))->getKey())->toBe($champion1->id);
        });

        test('firstChampion returns null when no championships', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::firstChampion($model))->toBeNull();
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

            $longest = TitleChampionshipQuery::longestChampionship($model);
            expect($longest)
                ->not->toBeNull()
                ->champion_id->toBe($champion2->id);
        });

        test('longestChampionship returns null when no championships', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::longestChampionship($model))->toBeNull();
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

            expect(requiredModel(TitleChampionshipQuery::longestChampion($model))->getKey())->toBe($champion2->id);
        });

        test('longestChampion returns null when no championships', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::longestChampion($model))->toBeNull();
        });
    });

    describe('utility methods', function () {
        test('reignCount returns correct number of reigns', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::reignCount($model))->toBe(0);

            TitleChampionship::factory()->create(['title_id' => $model->id]);
            TitleChampionship::factory()->create(['title_id' => $model->id]);

            expect(TitleChampionshipQuery::reignCount($model))->toBe(2);
        });

        test('isVacant returns true when no current champion', function () {
            $model = $this->model;
            expect(TitleChampionshipQuery::isVacant($model))->toBeTrue();
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

            expect(TitleChampionshipQuery::isVacant($model))->toBeFalse();
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
            expect(TitleChampionshipQuery::currentChampionship($model))->not->toBeNull();
            expect(requiredModel(TitleChampionshipQuery::currentChampionship($model))->lost_at)->toBeNull();
        });

        test('model can exist without championships', function () {
            $model = $this->model;
            expect($model->championships()->count())->toBe(0);
            expect(TitleChampionshipQuery::currentChampionship($model))->toBeNull();
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
