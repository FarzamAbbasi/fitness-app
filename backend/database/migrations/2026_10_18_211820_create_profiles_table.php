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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('goal'); // e.g. build_muscle, lose_fat, endurance, general_fitness
            $table->string('experience_level'); // beginner, intermediate, advanced
            $table->unsignedTinyInteger('days_per_week');
            $table->unsignedSmallInteger('session_duration_minutes');
            $table->json('equipment')->nullable(); // e.g. ["dumbbells", "bodyweight"]
            $table->string('niche')->nullable(); // chosen audience wedge, e.g. busy_professionals
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
