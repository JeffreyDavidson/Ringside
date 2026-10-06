<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default(MembershipRole::Member->value);
            $table->timestamps();
            $table->unique(['promotion_id', 'email']);
            $table->index('email');
        });
    }
};
