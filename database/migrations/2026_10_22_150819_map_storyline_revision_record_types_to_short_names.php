<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Revisions used to store the PHP class name; rows with any other value are left as they are. */
    public function up(): void
    {
        $shortNames = [
            'Storyline' => 'storyline',
            'StorylineStep' => 'step',
            'StorylineParticipant' => 'participant',
            'StorylineDevelopment' => 'development',
        ];

        foreach ($shortNames as $className => $shortName) {
            DB::table('storyline_revisions')
                ->where('record_type', $className)
                ->update(['record_type' => $shortName]);
        }
    }
};
