<?php

declare(strict_types=1);

use App\Models\Promotions\Promotion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->cascadeOnDelete();
            $table->uuid('client_id');
            $table->string('name', 160);
            $table->string('phone', 80)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['promotion_id', 'client_id']);
        });
    }
};
