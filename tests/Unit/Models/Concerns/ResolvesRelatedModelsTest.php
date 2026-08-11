<?php

declare(strict_types=1);

use App\Models\Concerns\ResolvesRelatedModels;

test('it reports a missing convention-based related model as a logic error', function () {
    $model = new class
    {
        use ResolvesRelatedModels;

        public function resolve(string $suffix): string
        {
            return $this->resolveRelatedModelClass($suffix);
        }
    };

    expect(fn () => $model->resolve('MissingRelationship'))
        ->toThrow(LogicException::class, 'Related model');
});

test('it reports whether a convention-based related model exists', function () {
    $model = new class
    {
        use ResolvesRelatedModels;

        public function relatedModelIsAvailable(string $suffix): bool
        {
            return $this->relatedModelExists($suffix);
        }
    };

    expect($model->relatedModelIsAvailable('MissingRelationship'))->toBeFalse();
});
