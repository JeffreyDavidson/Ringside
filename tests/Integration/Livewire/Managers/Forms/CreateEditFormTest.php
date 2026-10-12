<?php

declare(strict_types=1);

use App\Data\Managers\ManagerData;
use App\Livewire\Managers\Forms\CreateEditForm;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;
use Livewire\Component;

describe('manager create and edit form', function (): void {
    it('maps manager fields to typed application data', function (): void {
        // Arrange
        $employmentDate = Date::parse('2024-02-01');
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Paul';
        $form->last_name = 'Dangerously';
        $form->employment_date = $employmentDate->toDateString();

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(ManagerData::class)
            ->and($data->first_name)->toBe('Paul')
            ->and($data->last_name)->toBe('Dangerously')
            ->and($data->employment_date?->equalTo($employmentDate))->toBeTrue();
    });

    it('maps a missing employment date to null', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Jimmy';
        $form->last_name = 'Hart';
        $form->employment_date = null;

        // Act
        $data = $form->toData();

        // Assert
        expect($data->employment_date)->toBeNull();
    });

    it('hydrates and resolves the manager selected for editing', function (): void {
        // Arrange
        $manager = Manager::factory()->create([
            'first_name' => 'Bobby',
            'last_name' => 'Heenan',
        ]);
        $manager->employments()->create(['started_at' => '2024-01-15']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        // Act
        $form->setModel($manager);
        $selectedManager = $form->manager();

        // Assert
        expect($form->modelId)->toBe($manager->id)
            ->and($form->first_name)->toBe('Bobby')
            ->and($form->last_name)->toBe('Heenan')
            ->and($form->employment_date)->toBeNull()
            ->and($form->hasEmploymentHistory)->toBeTrue()
            ->and($selectedManager->is($manager))->toBeTrue();
    });
});

describe('manager form employment date time zone', function (): void {
    it('reads the entered day as the start of that day in the promotion time zone', function (
        string $timezone,
        string $now,
        string $enteredDay,
        string $expectedInstant,
        bool $isFuture,
    ): void {
        // Arrange
        Date::setTestNow($now);
        $promotion = Promotion::factory()->create(['timezone' => $timezone]);
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Paul';
        $form->last_name = 'Dangerously';
        $form->employment_date = $enteredDay;

        // Act
        $data = $form->toData();

        // Assert
        expect($data->employment_date?->utc()->toDateTimeString())->toBe($expectedInstant)
            ->and($data->employment_date?->isFuture())->toBe($isFuture);
    })->with([
        'west of UTC in its evening, tomorrow' => ['America/Los_Angeles', '2026-10-12 01:00:00', '2026-10-12', '2026-10-12 07:00:00', true],
        'west of UTC in its evening, today' => ['America/Los_Angeles', '2026-10-12 01:00:00', '2026-10-11', '2026-10-11 07:00:00', false],
        'east of UTC in its morning, today' => ['Australia/Sydney', '2026-10-10 22:00:00', '2026-10-11', '2026-10-10 13:00:00', false],
    ]);

    it('reads the entered day in the edited manager promotion time zone', function (): void {
        // Arrange
        Date::setTestNow('2026-10-10 22:00:00');
        $promotion = Promotion::factory()->create(['timezone' => 'Australia/Sydney']);
        $manager = Manager::factory()->for($promotion, 'promotion')->create();
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->setModel($manager);
        $form->first_name = 'Paul';
        $form->last_name = 'Dangerously';
        $form->employment_date = '2026-10-11';

        // Act
        $data = $form->toData();

        // Assert
        expect($data->employment_date?->utc()->toDateTimeString())->toBe('2026-10-10 13:00:00')
            ->and($data->employment_date?->isFuture())->toBeFalse();
    });
});
