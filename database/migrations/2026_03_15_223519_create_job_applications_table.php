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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('role_title');
            $table->string('job_url')->nullable();
            $table->enum('status', [
                'wishlist',
                'applied',
                'phone_screen',
                'interview',
                'offer',
                'rejected',
                'withdrawn'
            ])->default('wishlist');
            $table->tinyInteger('priority')->default(2); // 1=high 2=medium 3=low
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('location')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->date('applied_date')->nullable();
            $table->text('notes')->nullable();
            $table->integer('order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
