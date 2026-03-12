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
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('habit_categories')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('goal');
            $table->enum('goal_unit', ['days', 'weeks', 'months', 'years']);
            $table->enum('status', ['active', 'inactive', 'completed', 'paused'])->default('active');
            $table->date('start_date');
            $table->integer('deadline_value');
            $table->enum('deadline_unit', ['days', 'weeks', 'months', 'years']);
            $table->integer('repeat_count')->default(1);
            $table->integer('priority')->default(2); // 1=high, 2=medium, 3=low
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};
