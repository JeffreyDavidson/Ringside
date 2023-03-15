<?php

use App\Actions\Titles\RestoreAction;
use App\Models\Title;
use App\Repositories\TitleRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->titleRepository = mock(TitleRepository::class);
});

test('it restores a deleted title', function () {
    $title = Title::factory()->trashed()->create();

    $this->titleRepository
        ->shouldReceive('restore')
        ->once()
        ->with($title);

    RestoreAction::run($title);
});
