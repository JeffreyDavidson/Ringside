<?php

declare(strict_types=1);

use App\Models\Promotions\PromotionInvitation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invitations expire. Existing rows get the same lifetime counted from when they were created, which
     * is done in PHP so it behaves the same on every database engine. The column ends up NOT NULL with a
     * CURRENT_TIMESTAMP default, so a row written without an expiry (a raw insert) is expired rather than
     * immortal.
     */
    public function up(): void
    {
        Schema::table('promotion_invitations', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable()->index();
        });

        DB::table('promotion_invitations')
            ->orderBy('id')
            ->chunkById(500, function ($invitations): void {
                foreach ($invitations as $invitation) {
                    DB::table('promotion_invitations')
                        ->where('id', $invitation->id)
                        ->update([
                            'expires_at' => Carbon::parse($invitation->created_at ?? now())
                                ->addDays(PromotionInvitation::EXPIRES_AFTER_DAYS),
                        ]);
                }
            });

        Schema::table('promotion_invitations', function (Blueprint $table): void {
            $table->timestamp('expires_at')->useCurrent()->change();
        });
    }
};
