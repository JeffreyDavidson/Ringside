<?php

test('invoke restores a deleted tag team and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    $this->assertNull($this->tagTeam->fresh()->deleted_at);
});
