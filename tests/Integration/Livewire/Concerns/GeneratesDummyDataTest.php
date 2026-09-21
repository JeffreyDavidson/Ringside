<?php

declare(strict_types=1);

use App\Livewire\Concerns\GeneratesDummyData;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

describe('dummy data generation', function (): void {
    it('delegates population in permitted environments', function (string $environment): void {
        // Arrange
        $component = new class
        {
            use GeneratesDummyData;

            public string $name = '';

            protected function populateDummyData(): void
            {
                $this->name = 'Test Name';
            }
        };

        $originalEnvironment = app()->environment();
        app()->detectEnvironment(fn (): string => $environment);

        try {
            // Act
            $component->fillDummyFields();

            // Assert
            expect($component->name)->toBe('Test Name');
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }
    })->with([
        'local development' => ['local'],
        'automated testing' => ['testing'],
    ]);

    it('rejects requests without populating fields in other environments', function (string $environment): void {
        // Arrange
        $component = new class
        {
            use GeneratesDummyData;

            public string $name = 'Original Name';

            protected function populateDummyData(): void
            {
                $this->name = 'Generated Name';
            }
        };

        $originalEnvironment = app()->environment();
        app()->detectEnvironment(fn (): string => $environment);

        try {
            // Act / Assert
            expect(fn () => $component->fillDummyFields())->toThrow(NotFoundHttpException::class);

            expect($component->name)->toBe('Original Name');
        } finally {
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }
    })->with([
        'production' => ['production'],
        'staging' => ['staging'],
        'preview' => ['preview'],
    ]);

    it('allows each component to define its own generated values', function (): void {
        // Arrange
        $component = new class
        {
            use GeneratesDummyData;

            public ?string $generatedAt = null;

            protected function populateDummyData(): void
            {
                $this->generatedAt = 'generated';
            }
        };

        // Act
        $component->fillDummyFields();

        // Assert
        expect($component->generatedAt)->toBe('generated');
    });

    it('omits optional employment dates when generation is disabled', function (): void {
        // Arrange
        $component = new class
        {
            use GeneratesDummyData;

            public function employmentDate(): ?string
            {
                return $this->generateOptionalEmploymentDate(0.0);
            }

            protected function populateDummyData(): void {}
        };

        // Act
        $employmentDate = $component->employmentDate();

        // Assert
        expect($employmentDate)->toBeNull();
    });

    it('formats generated employment dates for date inputs', function (): void {
        // Arrange
        $component = new class
        {
            use GeneratesDummyData;

            public function employmentDate(): ?string
            {
                return $this->generateOptionalEmploymentDate(1.0);
            }

            protected function populateDummyData(): void {}
        };

        // Act
        $employmentDate = $component->employmentDate();

        // Assert
        expect($employmentDate)
            ->toBeString()
            ->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    });

    it('honors the supplied start-date format and generation bounds', function (): void {
        // Arrange
        $component = new class
        {
            use GeneratesDummyData;

            public function startDate(): ?string
            {
                return $this->generateOptionalStartDate(
                    format: 'd/m/Y H:i:s',
                    probability: 1.0,
                    minPeriod: '2020-02-03 12:34:56',
                    maxPeriod: '2020-02-03 12:34:56',
                );
            }

            protected function populateDummyData(): void {}
        };

        // Act
        $startDate = $component->startDate();

        // Assert
        expect($startDate)->toBe('03/02/2020 12:34:56');
    });
});
