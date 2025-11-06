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
        Schema::create('contact_merges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('secondary_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->json('merged_attributes')->nullable();
            $table->json('merged_custom_fields')->nullable();
            $table->json('merged_files')->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamp('merged_at')->useCurrent();
            $table->timestamps();
            $table->unique(['master_contact_id', 'secondary_contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_merges');
    }
};
