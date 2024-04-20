<?php

declare(strict_types=1);

use App\Actions\Titles\RestoreAction;
use App\Models\Title;
use App\Repositories\TitleRepository;

beforeEach(function () {
    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it restores a deleted title', function () {
    $title = Title::factory()->trashed()->create();

    $this->titleRepository
        ->shouldReceive('restore')
        ->once()
        ->with($title);

    RestoreAction::run($title);
});
