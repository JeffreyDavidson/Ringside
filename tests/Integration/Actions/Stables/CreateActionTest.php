<?php

declare(strict_types=1);

use App\Actions\Stables\AddMembersAction;
use App\Actions\Stables\CreateAction;
use App\Data\StableData;
use App\Models\Stable;
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
        new Collection(),
    );

    $this->stableRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns($stable = new Stable());

    AddMembersAction::shouldRun()
        ->with($stable, $data->wrestlers, $data->tagTeams, $data->managers);

    CreateAction::run($data);
});
