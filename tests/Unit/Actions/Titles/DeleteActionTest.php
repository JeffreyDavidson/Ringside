<?php

declare(strict_types=1);

use App\Actions\Titles\DeleteAction;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;

beforeEach(function () {
    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it deletes a title', function () {
    $title = Title::factory()->create();

    $this->titleRepository
        ->shouldReceive('delete')
        ->once()
        ->with($title);

    resolve(DeleteAction::class)->handle($title);
});
