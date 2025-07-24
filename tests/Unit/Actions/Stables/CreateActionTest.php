<?php

declare(strict_types=1);

use App\Actions\Stables\CreateAction;
use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Illuminate\Database\Eloquent\Collection;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it creates a stable', function () {
    $data = new StableData(
        'Example Stable Name',
        null,
        new Collection(),
        new Collection(),
    );

    $this->stableRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns($stable = new Stable());

    $this->stableRepository
        ->shouldReceive('addWrestlers')
        ->once()
        ->with($stable, $data->wrestlers, Mockery::type('Illuminate\Support\Carbon'));

    $this->stableRepository
        ->shouldReceive('addTagTeams')
        ->once()
        ->with($stable, $data->tagTeams, Mockery::type('Illuminate\Support\Carbon'));

    $this->stableRepository
        ->shouldNotReceive('createActivity');

    resolve(CreateAction::class)->handle($data);
});
