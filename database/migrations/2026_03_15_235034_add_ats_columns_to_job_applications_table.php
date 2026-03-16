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
        Schema::table('job_applications', function (Blueprint $table) {
            $table->tinyInteger('ats_score')->nullable()->after('notes');
            $table->json('ats_analysis')->nullable()->after('ats_score');
            $table->timestamp('ats_analyzed_at')->nullable()->after('ats_analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['ats_score', 'ats_analysis', 'ats_analyzed_at']);
        });
    }
};
