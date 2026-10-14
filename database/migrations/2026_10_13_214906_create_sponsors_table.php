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
        Schema::create('sponsors', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Promotion::class)->index()->constrained()->restrictOnDelete();
            $table->uuid('submission_key');
            $table->unsignedInteger('version');
            $table->string('name', 160);
            $table->string('contact_name', 160)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['promotion_id', 'submission_key']);
        });
    }
};
