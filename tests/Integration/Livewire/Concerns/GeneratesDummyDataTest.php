<?php

declare(strict_types=1);

use App\Livewire\Concerns\GeneratesDummyData;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

describe('GeneratesDummyData', function () {
    test('delegates population to the typed component implementation', function () {
        $component = new class
        {
            use GeneratesDummyData;

            public string $name = '';

            protected function populateDummyData(): void
            {
                $this->name = 'Test Name';
            }
        };

        $component->fillDummyFields();

        expect($component->name)->toBe('Test Name');
    });

    test('rejects dummy data requests outside local and testing environments', function () {
        $component = new class
        {
            use GeneratesDummyData;

            protected function populateDummyData(): void {}
        };

        app()->detectEnvironment(fn (): string => 'production');

        try {
            expect(fn () => $component->fillDummyFields())->toThrow(NotFoundHttpException::class);
        } finally {
            app()->detectEnvironment(fn (): string => 'testing');
        }
    });

    test('requires a typed population hook', function () {
        $method = new ReflectionMethod(GeneratesDummyData::class, 'populateDummyData');

        expect($method->isAbstract())->toBeTrue()
            ->and($method->isProtected())->toBeTrue()
            ->and(reflectionReturnTypeName($method))->toBe('void');
    });
});
