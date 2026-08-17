<?php

declare(strict_types=1);

use App\Livewire\Concerns\GeneratesDummyData;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Integration\Livewire\Concerns\GeneratesDummyDataTest;

/**
 * Unit tests for GeneratesDummyData trait structure.
 *
 * UNIT TEST SCOPE:
 * - Trait structure verification
 * - Method signatures and return types
 * - Method visibility and documentation
 * - Abstract method requirements
 * - Trait naming and namespace
 *
 * @see GeneratesDummyData
 * @see GeneratesDummyDataTest
 */
describe('GeneratesDummyData Unit Tests', function () {
    describe('trait structure', function () {
        test('is trait', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            expect($reflection->isTrait())->toBeTrue();
        });

        test('is abstract due to abstract method', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            expect($reflection->isAbstract())->toBeTrue();
        });

    });

    describe('public method signatures', function () {
        test('has fillDummyFields method', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);

            expect($reflection->hasMethod('fillDummyFields'))->toBeTrue();

            $method = $reflection->getMethod('fillDummyFields');
            expect($method->isPublic())->toBeTrue();
            expect(reflectionReturnTypeName($method))->toBe('void');
            expect($method->getNumberOfParameters())->toBe(0);
        });
    });

    describe('private helper method signatures', function () {
        test('has populateField method', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);

            expect($reflection->hasMethod('populateField'))->toBeTrue();

            $method = $reflection->getMethod('populateField');
            expect($method->isPrivate())->toBeTrue();
            expect(reflectionReturnTypeName($method))->toBe('void');
            expect($method->getNumberOfParameters())->toBe(2);

            $parameters = $method->getParameters();
            expect($parameters[0]->getName())->toBe('field');
            expect(reflectionTypeName($parameters[0]))->toBe('string');
            expect($parameters[1]->getName())->toBe('value');
            expect(reflectionTypeName($parameters[1]))->toBe('mixed');
        });

    });

    describe('protected generator method signatures', function () {
        test('has wrestling name generators', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);

            expect($reflection->hasMethod('generateWrestlingName'))->toBeTrue();
            expect($reflection->hasMethod('generateSignatureMove'))->toBeTrue();

            $nameMethod = $reflection->getMethod('generateWrestlingName');
            expect($nameMethod->isProtected())->toBeTrue();
            expect(reflectionReturnTypeName($nameMethod))->toBe('string');
            expect($nameMethod->getNumberOfParameters())->toBe(0);

            $moveMethod = $reflection->getMethod('generateSignatureMove');
            expect($moveMethod->isProtected())->toBeTrue();
            expect(reflectionReturnTypeName($moveMethod))->toBe('string');
            expect($moveMethod->getNumberOfParameters())->toBe(0);
        });

        test('has venue and title generators', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);

            expect($reflection->hasMethod('generateVenueName'))->toBeTrue();
            expect($reflection->hasMethod('generateChampionshipTitle'))->toBeTrue();

            $venueMethod = $reflection->getMethod('generateVenueName');
            expect($venueMethod->isProtected())->toBeTrue();
            expect(reflectionReturnTypeName($venueMethod))->toBe('string');
            expect($venueMethod->getNumberOfParameters())->toBe(0);

            $titleMethod = $reflection->getMethod('generateChampionshipTitle');
            expect($titleMethod->isProtected())->toBeTrue();
            expect(reflectionReturnTypeName($titleMethod))->toBe('string');
            expect($titleMethod->getNumberOfParameters())->toBe(0);
        });

        test('has address and date generators', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);

            expect($reflection->hasMethod('generateUSAddress'))->toBeTrue();
            expect($reflection->hasMethod('generateFutureDate'))->toBeTrue();

            $addressMethod = $reflection->getMethod('generateUSAddress');
            expect($addressMethod->isProtected())->toBeTrue();
            expect(reflectionReturnTypeName($addressMethod))->toBe('array');
            expect($addressMethod->getNumberOfParameters())->toBe(0);

            $dateMethod = $reflection->getMethod('generateFutureDate');
            expect($dateMethod->isProtected())->toBeTrue();
            expect(reflectionReturnTypeName($dateMethod))->toBe('string');
            expect(requiredReflectionType($dateMethod->getReturnType())->allowsNull())->toBeTrue();
            expect($dateMethod->getNumberOfParameters())->toBe(2);
        });
    });

    describe('abstract method requirements', function () {
        test('has getDummyDataFields abstract method', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);

            expect($reflection->hasMethod('getDummyDataFields'))->toBeTrue();

            $method = $reflection->getMethod('getDummyDataFields');
            expect($method->isAbstract())->toBeTrue();
            expect($method->isProtected())->toBeTrue();
            expect(reflectionReturnTypeName($method))->toBe('array');
            expect($method->getNumberOfParameters())->toBe(0);
        });
    });

    describe('namespace and naming', function () {
        test('uses correct namespace', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            expect($reflection->getNamespaceName())->toBe('App\\Livewire\\Concerns');
        });

        test('follows trait naming convention', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            expect($reflection->getShortName())->toBe('GeneratesDummyData');
        });
    });

    describe('dependency imports', function () {
        test('imports LogicException', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            $source = reflectionSource($reflection);

            expect($source)->toContain('use LogicException;');
        });
    });

    describe('trait method organization', function () {
        test('has correct method visibility distribution', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            $methods = array_filter(
                $reflection->getMethods(),
                fn ($method) => $method->getDeclaringClass()->getName() === GeneratesDummyData::class
            );

            $publicMethods = array_filter($methods, fn ($method) => $method->isPublic());
            $protectedMethods = array_filter($methods, fn ($method) => $method->isProtected());
            $privateMethods = array_filter($methods, fn ($method) => $method->isPrivate());

            expect($publicMethods)->toHaveCount(1); // fillDummyFields
            expect(count($protectedMethods))->toBeGreaterThan(5); // generators + abstract
            expect(array_values(array_map(
                fn (ReflectionMethod $method): string => $method->getName(),
                $privateMethods
            )))->toEqualCanonicalizing(['populateField', 'randomString', 'randomGenerator']);
        });

        test('has no properties', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            $properties = array_filter(
                $reflection->getProperties(),
                fn ($property) => $property->getDeclaringClass()->getName() === GeneratesDummyData::class
            );

            expect($properties)->toHaveCount(0);
        });
    });

    describe('field population', function () {
        test('rejects dummy data requests outside local and testing environments', function () {
            $form = new class
            {
                use GeneratesDummyData;

                public string $name = '';

                protected function getDummyDataFields(): array
                {
                    return ['name' => 'Test Name'];
                }
            };

            app()->detectEnvironment(fn () => 'production');

            try {
                expect(fn () => $form->fillDummyFields())->toThrow(NotFoundHttpException::class);
            } finally {
                app()->detectEnvironment(fn () => 'testing');
            }
        });

        test('populates fields directly on a form', function () {
            $form = new class
            {
                use GeneratesDummyData;

                public string $name = '';

                protected function getDummyDataFields(): array
                {
                    return ['name' => 'Test Name'];
                }
            };

            $form->fillDummyFields();

            expect($form->name)->toBe('Test Name');
        });

        test('populates fields on a nested form object', function () {
            $nestedForm = new class
            {
                public string $name = '';
            };
            $component = new class($nestedForm)
            {
                use GeneratesDummyData;

                public function __construct(public object $form) {}

                protected function getDummyDataFields(): array
                {
                    return ['name' => 'Test Name'];
                }
            };

            $component->fillDummyFields();

            expect($nestedForm->name)->toBe('Test Name');
        });

        test('propagates invalid field configuration', function () {
            $form = new class
            {
                use GeneratesDummyData;

                protected function getDummyDataFields(): array
                {
                    return ['missing' => 'Test Name'];
                }
            };

            expect(fn () => $form->fillDummyFields())
                ->toThrow(LogicException::class, 'Dummy data field [missing] is not defined');
        });

        test('propagates invalid generated value types', function () {
            $form = new class
            {
                use GeneratesDummyData;

                public int $count = 0;

                protected function getDummyDataFields(): array
                {
                    return ['count' => 'invalid'];
                }
            };

            expect(fn () => $form->fillDummyFields())->toThrow(TypeError::class);
        });
    });

    describe('generator method parameters', function () {
        test('generateFutureDate has proper parameters', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            $method = $reflection->getMethod('generateFutureDate');
            $parameters = $method->getParameters();

            expect($parameters[0]->getName())->toBe('probability');
            expect(reflectionTypeName($parameters[0]))->toBe('float');
            expect($parameters[0]->isOptional())->toBeTrue();
            expect($parameters[0]->getDefaultValue())->toBe(0.8);

            expect($parameters[1]->getName())->toBe('maxPeriod');
            expect(reflectionTypeName($parameters[1]))->toBe('string');
            expect($parameters[1]->isOptional())->toBeTrue();
            expect($parameters[1]->getDefaultValue())->toBe('+3 months');
        });
    });

    describe('return type annotations', function () {
        test('getDummyDataFields has proper return type annotation', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            $method = $reflection->getMethod('getDummyDataFields');
            $docComment = $method->getDocComment();

            expect($docComment)->toContain('@return array<string, callable|mixed>');
        });

        test('generateUSAddress has proper return type annotation', function () {
            $reflection = new ReflectionClass(GeneratesDummyData::class);
            $method = $reflection->getMethod('generateUSAddress');
            $docComment = $method->getDocComment();

            expect($docComment)->toContain('@return array<string, mixed>');
        });
    });
});
