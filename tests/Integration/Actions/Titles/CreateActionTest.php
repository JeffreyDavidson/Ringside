<?php

declare(strict_types=1);

use App\Actions\Titles\CreateAction;
use App\Data\Titles\TitleData;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Naming\GuardedName;
use App\Enums\Titles\TitleType;
use App\Exceptions\Titles\NameTakenException;
use App\Lifecycle\Naming\RecordNameLock;
use App\Models\Promotions\Promotion;
use App\Models\Titles\Title;
use App\Services\Promotions\PromotionContextService;

test('it creates a title', function () {
    $data = new TitleData('Example Title', TitleType::Singles, null);

    $result = resolve(CreateAction::class)->handle($data);

    expect($result)->toBeInstanceOf(Title::class)
        ->and($result->name)->toBe('Example Title')
        ->and($result->type)->toBe(TitleType::Singles)
        ->and($result->activityPeriods)->toBeEmpty();
});

test('it activates a title if activation date is filled in request', function () {
    $datetime = now();
    $data = new TitleData('Example Title', TitleType::Singles, $datetime);

    $result = resolve(CreateAction::class)->handle($data);

    expect($result)->toBeInstanceOf(Title::class)
        ->and($result->name)->toBe('Example Title')
        ->and($result->type)->toBe(TitleType::Singles)
        ->and($result->activityPeriods)->toHaveCount(1)
        ->and(requiredDate($result->activityPeriods->firstOrFail()->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});

test('it records a single debuted transition when a debut date is provided', function () {
    $datetime = now();
    $data = new TitleData('Example Title', TitleType::Singles, $datetime);

    $result = resolve(CreateAction::class)->handle($data);

    $transition = $result->lifecycleTransitions()->sole();
    expect($transition->transition)->toBe(LifecycleTransitionType::Debuted)
        ->and($transition->effective_at->toDateTimeString())->toBe($datetime->toDateTimeString());
});

test('it records no lifecycle transition when no debut date is provided', function () {
    $data = new TitleData('Example Title', TitleType::Singles, null);

    $result = resolve(CreateAction::class)->handle($data);

    expect($result->lifecycleTransitions()->exists())->toBeFalse();
});

describe('title name guard', function (): void {
    test('it locks the title name of the promotion before inserting the title', function () {
        // Arrange
        $promotion = Promotion::factory()->create();
        $context = resolve(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();
        $data = new TitleData('Example Title', TitleType::Singles, null);
        $nameKey = resolve(RecordNameLock::class)->key(GuardedName::TitleName, $promotion->id, 'Example Title');

        // Act
        $statements = recordStatements(fn () => resolve(CreateAction::class)->handle($data));

        // Assert
        $lockPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "record_name_locks"'));
        $insertPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "titles"'));

        expect($statements[$lockPosition]['bindings'])->toBe([$nameKey])
            ->and($lockPosition)->toBeLessThan($insertPosition);
    });

    test('it rejects a name another title of the promotion already uses, even a deleted one', function (bool $deleted) {
        // Arrange
        $promotion = Promotion::factory()->create();
        $context = resolve(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();
        $existing = Title::factory()->for($promotion, 'promotion')->create(['name' => 'Example Title']);

        if ($deleted) {
            $existing->delete();
        }

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(new TitleData('Example Title', TitleType::Singles, null));

        // Assert
        expect($create)->toThrow(NameTakenException::class, "A title named 'Example Title' already exists in this promotion.")
            ->and(Title::query()->withoutGlobalScopes()->where('name', 'Example Title')->count())->toBe(1);
    })->with([
        'live' => [false],
        'deleted' => [true],
    ]);

    test('it rejects a name another title without a promotion already uses', function () {
        // Arrange
        Title::factory()->create(['name' => 'Example Title']);

        // Act
        $create = fn () => resolve(CreateAction::class)->handle(new TitleData('Example Title', TitleType::Singles, null));

        // Assert
        expect($create)->toThrow(NameTakenException::class)
            ->and(Title::query()->where('name', 'Example Title')->count())->toBe(1);
    });

    test('it allows a name only another promotion uses', function () {
        // Arrange
        Title::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Example Title']);
        $promotion = Promotion::factory()->create();
        $context = resolve(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();

        // Act
        $title = resolve(CreateAction::class)->handle(new TitleData('Example Title', TitleType::Singles, null));

        // Assert
        expect($title->promotion_id)->toBe($promotion->id);
    });
});
