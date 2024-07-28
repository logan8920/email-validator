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
        Schema::create('batch_process_ids', function (Blueprint $table) {
            $table->id();
            $table->string('file_name',256)->nullable();
            $table->unsignedInteger('total_jobs')->nullable();
            $table->unsignedInteger('job_completed')->default(0)->nullable();
            $table->enum('status',[0,1,2])->default(0)->comment('0=>pending,1=>completed,2=>failed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_process_ids');
    }
};
