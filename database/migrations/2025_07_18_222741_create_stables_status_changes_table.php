<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stables_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stable_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('changed_at');
            $table->timestamps();
            
            $table->index(['stable_id', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stables_status_changes');
    }
};
