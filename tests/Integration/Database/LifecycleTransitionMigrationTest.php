<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Stables\Stable;
use App\Models\Titles\Title;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('legacy stable and title status history is preserved as lifecycle transitions', function () {
    Schema::create('titles_status_changes', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('title_id');
        $table->string('status');
        $table->timestamp('changed_at');
        $table->timestamps();
    });
    Schema::create('stables_status_changes', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('stable_id');
        $table->string('status');
        $table->timestamp('changed_at');
        $table->timestamps();
    });

    $title = Title::factory()->create();
    $stable = Stable::factory()->create();
    $changedAt = now()->subYear()->startOfSecond();

    DB::table('titles_status_changes')->insert([
        'title_id' => $title->id,
        'status' => 'active',
        'changed_at' => $changedAt,
        'created_at' => $changedAt,
        'updated_at' => $changedAt,
    ]);
    DB::table('stables_status_changes')->insert([
        'stable_id' => $stable->id,
        'status' => 'inactive',
        'changed_at' => $changedAt,
        'created_at' => $changedAt,
        'updated_at' => $changedAt,
    ]);

    $migration = require database_path('migrations/2026_08_12_160736_migrate_legacy_status_changes_to_lifecycle_transitions.php');
    $migration->up();

    $titleTransition = $title->lifecycleTransitions()->sole();
    $stableTransition = $stable->lifecycleTransitions()->sole();

    expect($titleTransition->transition)->toBe(LifecycleTransitionType::LegacyStatusChanged)
        ->and($titleTransition->context)->toBe(['status' => 'active'])
        ->and($stableTransition->transition)->toBe(LifecycleTransitionType::LegacyStatusChanged)
        ->and($stableTransition->context)->toBe(['status' => 'inactive']);

    $dropMigration = require database_path('migrations/2026_08_12_160742_remove_legacy_status_change_tables.php');
    $dropMigration->up();

    expect(Schema::hasTable('titles_status_changes'))->toBeFalse()
        ->and(Schema::hasTable('stables_status_changes'))->toBeFalse();
});
