<?php

declare(strict_types=1);

use App\Livewire\Concerns\GeneratesDummyData;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

describe('dummy data generation', function (): void {
    it('delegates population to the typed component implementation', function (): void {
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

        // Act
        $component->fillDummyFields();

        // Assert
        expect($component->name)->toBe('Test Name');
    });

    it('rejects requests outside local and testing environments', function (): void {
        // Arrange
        $component = new class
        {
            use GeneratesDummyData;

            protected function populateDummyData(): void {}
        };

        app()->detectEnvironment(fn (): string => 'production');

        // Act / Assert
        try {
            expect(fn () => $component->fillDummyFields())->toThrow(NotFoundHttpException::class);
        } finally {
            app()->detectEnvironment(fn (): string => 'testing');
        }
    });

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
});
