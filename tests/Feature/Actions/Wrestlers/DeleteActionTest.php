<?php

use App\Actions\Wrestlers\DeleteAction;
use App\Models\Wrestler;

test('it deletes a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    DeleteAction::run($wrestler);

    $this->assertSoftDeleted($wrestler);
});
