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
        Schema::create('treatment_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_report_id')->constrained('log_reports')->onDelete('cascade');
            $table->string('metric')->nullable();
            $table->text('recommendation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatment_recommendations');
    }
};
