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
        Schema::create('plan_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_day_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->string('exercise_name');
            $table->unsignedTinyInteger('sets')->nullable();
            $table->string('reps')->nullable(); // string to allow ranges like "8-12" or "AMRAP"
            $table->unsignedSmallInteger('duration_seconds')->nullable(); // for timed exercises
            $table->unsignedSmallInteger('rest_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_exercises');
    }
};
