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
        Schema::create('log_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('process')->nullable();
            $table->decimal('pot', 14, 3)->nullable();
            $table->decimal('pot_shift_tersedia', 14, 3)->nullable();
            $table->decimal('unschedule_time', 14, 3)->nullable();
            $table->decimal('unschedule_time_shift_tersedia', 14, 3)->nullable();
            $table->decimal('waktu_berproduksi', 14, 3)->nullable();
            $table->decimal('idle_time', 14, 3)->nullable();
            $table->decimal('l2', 14, 3)->nullable();
            $table->decimal('l21', 14, 3)->nullable();
            $table->decimal('l22', 14, 3)->nullable();
            $table->decimal('ig', 14, 3)->nullable();
            $table->decimal('ppt', 14, 3)->nullable();
            $table->decimal('r', 14, 3)->nullable();
            $table->decimal('dt', 14, 3)->nullable();
            $table->decimal('setup', 14, 3)->nullable();
            $table->decimal('p6', 14, 3)->nullable();
            $table->decimal('p5', 14, 3)->nullable();
            $table->decimal('p8', 14, 3)->nullable();
            $table->decimal('p9', 14, 3)->nullable();
            $table->decimal('breakdown', 14, 3)->nullable();
            $table->decimal('m1', 14, 3)->nullable();
            $table->decimal('m2', 14, 3)->nullable();
            $table->decimal('m4', 14, 3)->nullable();
            $table->decimal('m8', 14, 3)->nullable();
            $table->decimal('m9', 14, 3)->nullable();
            $table->decimal('clean', 14, 3)->nullable();
            $table->decimal('p2', 14, 3)->nullable();
            $table->decimal('p4', 14, 3)->nullable();
            $table->decimal('p17', 14, 3)->nullable();
            $table->decimal('p19', 14, 3)->nullable();
            $table->decimal('p12', 14, 3)->nullable();
            $table->decimal('trial', 14, 3)->nullable();
            $table->decimal('r1', 14, 3)->nullable();
            $table->decimal('r2', 14, 3)->nullable();
            $table->decimal('waiting', 14, 3)->nullable();
            $table->decimal('l1', 14, 3)->nullable();
            $table->decimal('l3', 14, 3)->nullable();
            $table->decimal('h1', 14, 3)->nullable();
            $table->decimal('h2', 14, 3)->nullable();
            $table->decimal('h4', 14, 3)->nullable();
            $table->decimal('h6', 14, 3)->nullable();
            $table->decimal('h7', 14, 3)->nullable();
            $table->decimal('h8', 14, 3)->nullable();
            $table->decimal('h10', 14, 3)->nullable();
            $table->decimal('h11', 14, 3)->nullable();
            $table->decimal('h13', 14, 3)->nullable();
            $table->decimal('h14', 14, 3)->nullable();
            $table->decimal('h16', 14, 3)->nullable();
            $table->decimal('m5', 14, 3)->nullable();
            $table->decimal('m6', 14, 3)->nullable();
            $table->decimal('m7', 14, 3)->nullable();
            $table->decimal('q1', 14, 3)->nullable();
            $table->decimal('q2', 14, 3)->nullable();
            $table->decimal('q3', 14, 3)->nullable();
            $table->decimal('q4', 14, 3)->nullable();
            $table->decimal('total_output', 14, 3)->nullable();
            $table->decimal('reject_output', 14, 3)->nullable();
            $table->decimal('good_output', 14, 3)->nullable();
            $table->unsignedInteger('bulan');
            $table->unsignedInteger('tahun');
            $table->double('performance', 8, 2)->nullable();
            $table->double('availability', 8, 2)->nullable();
            $table->double('quality', 8, 2)->nullable();
            $table->double('oee', 8, 2)->nullable();
            $table->float('cluster')->nullable();
            $table->unsignedInteger('status')->nullable();
            $table->boolean('is_anomaly')->default(false);
            $table->float('x_axis')->nullable();
            $table->float('y_axis')->nullable();
            $table->float('silhouette_score')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_reports');
    }
};
