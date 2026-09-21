<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existingTransitionCount = DB::table('lifecycle_transitions')->count();
        $legacyStatusChangeCount = DB::table('titles_status_changes')->count()
            + DB::table('stables_status_changes')->count();

        DB::table('titles_status_changes')
            ->orderBy('id')
            ->eachById(function (object $statusChange): void {
                DB::table('lifecycle_transitions')->insert([
                    'subject_type' => 'title',
                    'subject_id' => $statusChange->title_id,
                    'dimension' => LifecycleDimension::Activity->value,
                    'transition' => LifecycleTransitionType::LegacyStatusChanged->value,
                    'effective_at' => $statusChange->changed_at,
                    'user_id' => null,
                    'context' => json_encode(['status' => $statusChange->status], JSON_THROW_ON_ERROR),
                    'created_at' => $statusChange->created_at,
                    'updated_at' => $statusChange->updated_at,
                ]);
            });

        DB::table('stables_status_changes')
            ->orderBy('id')
            ->eachById(function (object $statusChange): void {
                DB::table('lifecycle_transitions')->insert([
                    'subject_type' => 'stable',
                    'subject_id' => $statusChange->stable_id,
                    'dimension' => LifecycleDimension::Activity->value,
                    'transition' => LifecycleTransitionType::LegacyStatusChanged->value,
                    'effective_at' => $statusChange->changed_at,
                    'user_id' => null,
                    'context' => json_encode(['status' => $statusChange->status], JSON_THROW_ON_ERROR),
                    'created_at' => $statusChange->created_at,
                    'updated_at' => $statusChange->updated_at,
                ]);
            });

        if (DB::table('lifecycle_transitions')->count() !== $existingTransitionCount + $legacyStatusChangeCount) {
            throw new RuntimeException('Legacy status-change records were not fully transferred.');
        }
    }
};
