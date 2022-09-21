<?php

use App\Actions\Wrestlers\CreateAction;
use App\Data\WrestlerData;
use App\Http\Requests\Wrestlers\StoreRequest;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use function Spatie\PestPluginTestTime\testTime;

test('it creates a wrestler', function () {
    $requestData = StoreRequest::factory()->create([
        'name' => 'Example Wrestler Name',
        'feet' => 6,
        'inches' => 10,
        'weight' => 300,
        'hometown' => 'Laraville, New York',
        'signature_move' => null,
        'start_date' => null,
    ]);
    $data = WrestlerData::fromStoreRequest($requestData);

    CreateAction::run($data);

    expect(Wrestler::latest()->first())
        ->name->toBe('Example Wrestler Name')
        ->height->toBe(82)
        ->weight->toBe(300)
        ->hometown->toBe('Laraville, New York')
        ->signature_move->toBeNull()
        ->employments->toBeEmpty();
});

test('it creates a wrestler with a signature move and redirects', function () {
    $data = StoreRequest::factory()->create([
        'signature_move' => 'Example Finishing Move',
    ]);

    CreateAction::run($data);

    expect(Wrestler::latest()->first())
        ->signature_move->toBe('Example Finishing Move');
});

test('an employment is created for the wrestler if start date is filled in request', function () {
    testTime()->freeze();
    $dateTime = Carbon::now();
    $data = StoreRequest::factory()->create([
        'start_date' => $dateTime,
    ]);

    CreateAction::run($data);

    expect(Wrestler::latest()->first())
        ->employments->toHaveCount(1)
        ->employments->first()->started_at->toDateTimeString()->toBe($dateTime);
});
