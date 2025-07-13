<?php

declare(strict_types=1);

use App\Actions\Stables\CreateAction;
use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;
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
        new Collection(),
    );

    $stable = new Stable();

    $this->stableRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($stable);

    // Should add empty collections (CreateAction always calls these)
    $this->stableRepository
        ->shouldReceive('addWrestlers')
        ->once()
        ->with($stable, $data->wrestlers, Mockery::type(Carbon::class));
    $this->stableRepository
        ->shouldReceive('addTagTeams')
        ->once()
        ->with($stable, $data->tagTeams, Mockery::type(Carbon::class));
    $this->stableRepository
        ->shouldReceive('addManagers')
        ->once()
        ->with($stable, $data->managers, Mockery::type(Carbon::class));

    // Should not create activity since no start_date provided
    $this->stableRepository
        ->shouldNotReceive('createActivity');

    resolve(CreateAction::class)->handle($data);
});
