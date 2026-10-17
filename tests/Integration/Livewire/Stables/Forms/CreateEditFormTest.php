<?php

declare(strict_types=1);

use App\Data\Stables\StableData;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Livewire\Stables\Forms\CreateEditForm;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use JMac\Testing\Double;
use Livewire\Component;

/** A form wired like Livewire does on every request: constructed for a component, then booted with its injected collaborators. */
function bootedStableForm(): CreateEditForm
{
    $form = new CreateEditForm(Double::for(Component::class), 'form');
    $form->boot(resolve(StableActivityEligibility::class));

    return $form;
}

/** @return array<int, string> */
function validationErrorKeys(Closure $validation): array
{
    try {
        $validation();
    } catch (ValidationException $exception) {
        return array_keys($exception->errors());
    }

    return [];
}

describe('stable create and edit form', function (): void {
    it('maps stable fields and selected members to typed application data', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(2)->create();
        $tagTeam = TagTeam::factory()->create();
        $startDate = Date::parse('2024-01-01');
        $endDate = Date::parse('2024-12-31');
        $form = bootedStableForm();
        $form->name = 'The Four Horsemen';
        $form->started_at = $startDate->toDateString();
        $form->ended_at = $endDate->toDateString();
        $form->wrestlers = array_reverse($wrestlers->modelKeys());
        $form->tag_teams = [$tagTeam->id];

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(StableData::class)
            ->and($data->name)->toBe('The Four Horsemen')
            ->and($data->start_date?->equalTo($startDate))->toBeTrue()
            ->and($data->end_date?->equalTo($endDate))->toBeTrue()
            ->and($data->members->wrestlers?->map(fn (Wrestler $wrestler): int => $wrestler->id)->all())
            ->toBe($wrestlers->modelKeys())
            ->and($data->members->tagTeams?->map(fn (TagTeam $selectedTagTeam): int => $selectedTagTeam->id)->all())
            ->toBe([$tagTeam->id]);
    });

    it('maps missing activity dates and member selections to empty values', function (): void {
        // Arrange
        $form = bootedStableForm();
        $form->name = 'Future Faction';

        // Act
        $data = $form->toData();

        // Assert
        expect($data->start_date)->toBeNull()
            ->and($data->end_date)->toBeNull()
            ->and($data->members->wrestlers?->isEmpty())->toBeTrue()
            ->and($data->members->tagTeams?->isEmpty())->toBeTrue();
    });

    it('hydrates and resolves the stable selected for editing', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $stable = Stable::factory()->create(['name' => 'The Dangerous Alliance']);
        $stable->activityPeriods()->create([
            'started_at' => '2024-01-01',
            'ended_at' => '2024-12-31',
        ]);
        $stable->wrestlers()->attach($wrestler, ['joined_at' => '2024-01-01']);
        $stable->tagTeams()->attach($tagTeam, ['joined_at' => '2024-01-01']);
        $form = bootedStableForm();

        // Act
        $form->setModel($stable);
        $selectedStable = $form->stable();

        // Assert
        expect($form->modelId)->toBe($stable->id)
            ->and($form->name)->toBe('The Dangerous Alliance')
            ->and($form->started_at)->toBe('2024-01-01')
            ->and($form->ended_at)->toBe('2024-12-31')
            ->and($form->wrestlers)->toBe([$wrestler->id])
            ->and($form->tag_teams)->toBe([$tagTeam->id])
            ->and($selectedStable->is($stable))->toBeTrue();
    });

    it('treats a numeric string model key as the stable being edited when checking membership', function (): void {
        // Arrange
        $memberOfOtherStable = Wrestler::factory()->bookable()->create();
        $otherStable = Stable::factory()->create();
        $otherStable->wrestlers()->attach($memberOfOtherStable, ['joined_at' => '2024-01-01']);
        $member = Wrestler::factory()->bookable()->create();
        $stable = Stable::factory()->create(['name' => 'The Alliance']);
        $stable->wrestlers()->attach($member, ['joined_at' => '2024-01-01']);
        $form = bootedStableForm();
        $form->modelId = (string) $stable->id;
        $form->name = 'The Alliance';

        // Act
        $form->wrestlers = [$member->id];
        $currentMemberErrors = validationErrorKeys(fn () => $form->validate());
        $form->wrestlers = [$memberOfOtherStable->id];
        $otherStableMemberErrors = validationErrorKeys(fn () => $form->validate());

        // Assert
        expect($currentMemberErrors)->toBeEmpty()
            ->and($otherStableMemberErrors)->toBe(['form.wrestlers.0']);
    });
});

describe('stable form activity end date', function (): void {
    it('requires an end date for a disbanded stable instead of reopening it', function (): void {
        // Arrange
        $stable = Stable::factory()->inactive()->create();
        $form = bootedStableForm();
        $form->setModel($stable);
        $form->ended_at = '';

        // Act
        $errorKeys = validationErrorKeys(fn () => $form->validate());

        // Assert
        expect($errorKeys)->toContain('form.ended_at');
    });
});

describe('stable form start date time zone', function (): void {
    it('reads the entered start day as the start of that day in the promotion time zone', function (
        string $timezone,
        string $enteredDay,
        string $expectedInstant,
    ): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => $timezone]);
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();
        $form = bootedStableForm();
        $form->name = 'The Alliance';
        $form->started_at = $enteredDay;

        // Act
        $data = $form->toData();

        // Assert
        expect($data->start_date?->utc()->toDateTimeString())->toBe($expectedInstant);
    })->with([
        'west of UTC' => ['America/Los_Angeles', '2026-10-11', '2026-10-11 07:00:00'],
        'east of UTC' => ['Australia/Sydney', '2026-10-11', '2026-10-10 13:00:00'],
    ]);

    it('shows the stored start day back as the promotion day', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create(['timezone' => 'America/Los_Angeles']);
        $stable = Stable::factory()->for($promotion, 'promotion')->create();
        $stable->activityPeriods()->create(['started_at' => '2026-10-12 01:00:00']);
        $form = bootedStableForm();

        // Act
        $form->setModel($stable);

        // Assert
        expect($form->started_at)->toBe('2026-10-11');
    });
});

describe('stable form member join date', function (): void {
    it('lets a member hired after the stable started join an existing stable', function (Closure $makeMember, string $property): void {
        // Arrange
        $stable = Stable::factory()->active()->create();
        $stable->activityPeriods()->update(['started_at' => now()->subWeek()]);
        $member = $makeMember();
        $form = bootedStableForm();
        $form->setModel($stable);
        $form->{$property} = [...$form->{$property}, $member->id];

        // Act
        $errorKeys = validationErrorKeys(fn () => $form->validate());

        // Assert
        expect($errorKeys)->toBeEmpty();
    })->with([
        'wrestler' => [fn (): Wrestler => Wrestler::factory()->employed()->create(), 'wrestlers'],
        'tag team' => [fn (): TagTeam => TagTeam::factory()->employed()->create(), 'tag_teams'],
    ]);

    it('refuses a member who is not employed yet on the day they would join an existing stable', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();
        $stable->activityPeriods()->update(['started_at' => now()->subWeek()]);
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();
        $form = bootedStableForm();
        $form->setModel($stable);
        $form->wrestlers = [...$form->wrestlers, $wrestler->id];

        // Act
        $errorKeys = validationErrorKeys(fn () => $form->validate());

        // Assert
        expect($errorKeys)->toBe(['form.wrestlers.2']);
    });

    it('still refuses a member hired after the start date of a stable being created', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->employed()->create();
        $form = bootedStableForm();
        $form->name = 'Late Hires';
        $form->started_at = now()->subWeek()->toDateString();
        $form->wrestlers = [$wrestler->id];

        // Act
        $errorKeys = validationErrorKeys(fn () => $form->validate());

        // Assert
        expect($errorKeys)->toContain('form.wrestlers.0');
    });

    it('does not re-judge existing members hired after the stable started when renaming it', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();
        $stable->activityPeriods()->update(['started_at' => now()->subWeek()]);
        $form = bootedStableForm();
        $form->setModel($stable);
        $form->name = 'Renamed Faction';

        // Act
        $errorKeys = validationErrorKeys(fn () => $form->validate());

        // Assert
        expect($errorKeys)->toBeEmpty();
    });
});
