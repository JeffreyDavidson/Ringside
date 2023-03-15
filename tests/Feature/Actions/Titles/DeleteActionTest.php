<?php

use App\Actions\Titles\DeleteAction;
use App\Models\Title;
use App\Repositories\TitleRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->titleRepository = mock(TitleRepository::class);
});

test('it deletes a title', function () {
    $title = Title::factory()->create();

    $this->titleRepository
        ->shouldReceive('delete')
        ->once()
        ->with($title);

    DeleteAction::run($title);
});
