<?php

declare(strict_types=1);

use App\Actions\Titles\DeleteAction;
use App\Models\Title;
use App\Repositories\TitleRepository;

beforeEach(function () {
    $this->titleRepository = Mockery::mock(TitleRepository::class);
});

test('it deletes a title', function () {
    $title = Title::factory()->create();

    $this->titleRepository
        ->shouldReceive('delete')
        ->once()
        ->with($title);

    DeleteAction::run($title);
});
