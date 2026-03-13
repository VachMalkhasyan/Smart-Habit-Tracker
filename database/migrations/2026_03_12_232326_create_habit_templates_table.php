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
        Schema::create('habit_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('icon')->default('⭐');
            $table->integer('goal')->default(30);
            $table->enum('goal_unit', ['days', 'weeks', 'months', 'years'])->default('days');
            $table->integer('repeat_count')->default(1);
            $table->integer('deadline_value')->default(30);
            $table->enum('deadline_unit', ['days', 'weeks', 'months', 'years'])->default('days');
            $table->integer('priority')->default(2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habit_templates');
    }
};
