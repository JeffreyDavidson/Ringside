<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_user', function (Blueprint $table): void {
            $table->foreignIdFor(Promotion::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('role')->default(MembershipRole::Member->value);
            $table->string('status')->default(MembershipStatus::Invited->value);
            $table->timestamps();
            $table->unique(['promotion_id', 'user_id']);
        });
    }
};
