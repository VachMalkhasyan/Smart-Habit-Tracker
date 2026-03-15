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
        Schema::create('mood_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('score');        // 1-5
            $table->string('emoji');             // 😢😕😐🙂😄
            $table->string('label');             // Terrible, Bad, Okay, Good, Amazing
            $table->text('note')->nullable();    // optional free text
            $table->json('tags')->nullable();    // ["tired", "stressed", "motivated"]
            $table->date('logged_date');         // one per day
            $table->timestamps();

            $table->unique(['user_id', 'logged_date']); // one mood per day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mood_logs');
    }
};
