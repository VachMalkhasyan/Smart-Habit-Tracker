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
        Schema::table('habits', function (Blueprint $table) {
            $table->time('reminder_time')->nullable()->after('status');
            $table->integer('deadline_value')->nullable()->change();
            $table->enum('deadline_unit', ['days', 'weeks', 'months', 'years'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn('reminder_time');
            $table->integer('deadline_value')->nullable(false)->change();
            $table->enum('deadline_unit', ['days', 'weeks', 'months', 'years'])->nullable(false)->change();
        });
    }
};
