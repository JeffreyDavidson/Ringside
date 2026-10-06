<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_user', function (Blueprint $table): void {
            $table->string('status')->default(MembershipStatus::Active->value)->change();
        });
    }
};
