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
        Schema::create('machine_averages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('process')->nullable();
            $table->decimal('pot', 14, 3)->nullable();
            $table->decimal('pot_sd', 14, 3)->nullable();
            $table->decimal('pot_shift_tersedia', 14, 3)->nullable();
            $table->decimal('pot_shift_tersedia_sd', 14, 3)->nullable();
            $table->decimal('unschedule_time', 14, 3)->nullable();
            $table->decimal('unschedule_time_sd', 14, 3)->nullable();
            $table->decimal('unschedule_time_shift_tersedia', 14, 3)->nullable();
            $table->decimal('unschedule_time_shift_tersedia_sd', 14, 3)->nullable();
            $table->decimal('waktu_berproduksi', 14, 3)->nullable();
            $table->decimal('waktu_berproduksi_sd', 14, 3)->nullable();
            $table->decimal('idle_time', 14, 3)->nullable();
            $table->decimal('idle_time_sd', 14, 3)->nullable();
            $table->decimal('l2', 14, 3)->nullable();
            $table->decimal('l2_sd', 14, 3)->nullable();
            $table->decimal('l21', 14, 3)->nullable();
            $table->decimal('l21_sd', 14, 3)->nullable();
            $table->decimal('l22', 14, 3)->nullable();
            $table->decimal('l22_sd', 14, 3)->nullable();
            $table->decimal('ig', 14, 3)->nullable();
            $table->decimal('ig_sd', 14, 3)->nullable();
            $table->decimal('ppt', 14, 3)->nullable();
            $table->decimal('ppt_sd', 14, 3)->nullable();
            $table->decimal('r', 14, 3)->nullable();
            $table->decimal('r_sd', 14, 3)->nullable();
            $table->decimal('dt', 14, 3)->nullable();
            $table->decimal('dt_sd', 14, 3)->nullable();
            $table->decimal('setup', 14, 3)->nullable();
            $table->decimal('setup_sd', 14, 3)->nullable();
            $table->decimal('p6', 14, 3)->nullable();
            $table->decimal('p6_sd', 14, 3)->nullable();
            $table->decimal('p5', 14, 3)->nullable();
            $table->decimal('p5_sd', 14, 3)->nullable();
            $table->decimal('p8', 14, 3)->nullable();
            $table->decimal('p8_sd', 14, 3)->nullable();
            $table->decimal('p9', 14, 3)->nullable();
            $table->decimal('p9_sd', 14, 3)->nullable();
            $table->decimal('breakdown', 14, 3)->nullable();
            $table->decimal('breakdown_sd', 14, 3)->nullable();
            $table->decimal('m1', 14, 3)->nullable();
            $table->decimal('m1_sd', 14, 3)->nullable();
            $table->decimal('m2', 14, 3)->nullable();
            $table->decimal('m2_sd', 14, 3)->nullable();
            $table->decimal('m4', 14, 3)->nullable();
            $table->decimal('m4_sd', 14, 3)->nullable();
            $table->decimal('m8', 14, 3)->nullable();
            $table->decimal('m8_sd', 14, 3)->nullable();
            $table->decimal('m9', 14, 3)->nullable();
            $table->decimal('m9_sd', 14, 3)->nullable();
            $table->decimal('clean', 14, 3)->nullable();
            $table->decimal('clean_sd', 14, 3)->nullable();
            $table->decimal('p2', 14, 3)->nullable();
            $table->decimal('p2_sd', 14, 3)->nullable();
            $table->decimal('p4', 14, 3)->nullable();
            $table->decimal('p4_sd', 14, 3)->nullable();
            $table->decimal('p17', 14, 3)->nullable();
            $table->decimal('p17_sd', 14, 3)->nullable();
            $table->decimal('p19', 14, 3)->nullable();
            $table->decimal('p19_sd', 14, 3)->nullable();
            $table->decimal('p12', 14, 3)->nullable();
            $table->decimal('p12_sd', 14, 3)->nullable();
            $table->decimal('trial', 14, 3)->nullable();
            $table->decimal('trial_sd', 14, 3)->nullable();
            $table->decimal('r1', 14, 3)->nullable();
            $table->decimal('r1_sd', 14, 3)->nullable();
            $table->decimal('r2', 14, 3)->nullable();
            $table->decimal('r2_sd', 14, 3)->nullable();
            $table->decimal('waiting', 14, 3)->nullable();
            $table->decimal('waiting_sd', 14, 3)->nullable();
            $table->decimal('l1', 14, 3)->nullable();
            $table->decimal('l1_sd', 14, 3)->nullable();
            $table->decimal('l3', 14, 3)->nullable();
            $table->decimal('l3_sd', 14, 3)->nullable();
            $table->decimal('h1', 14, 3)->nullable();
            $table->decimal('h1_sd', 14, 3)->nullable();
            $table->decimal('h2', 14, 3)->nullable();
            $table->decimal('h2_sd', 14, 3)->nullable();
            $table->decimal('h4', 14, 3)->nullable();
            $table->decimal('h4_sd', 14, 3)->nullable();
            $table->decimal('h6', 14, 3)->nullable();
            $table->decimal('h6_sd', 14, 3)->nullable();
            $table->decimal('h7', 14, 3)->nullable();
            $table->decimal('h7_sd', 14, 3)->nullable();
            $table->decimal('h8', 14, 3)->nullable();
            $table->decimal('h8_sd', 14, 3)->nullable();
            $table->decimal('h10', 14, 3)->nullable();
            $table->decimal('h10_sd', 14, 3)->nullable();
            $table->decimal('h11', 14, 3)->nullable();
            $table->decimal('h11_sd', 14, 3)->nullable();
            $table->decimal('h13', 14, 3)->nullable();
            $table->decimal('h13_sd', 14, 3)->nullable();
            $table->decimal('h14', 14, 3)->nullable();
            $table->decimal('h14_sd', 14, 3)->nullable();
            $table->decimal('h16', 14, 3)->nullable();
            $table->decimal('h16_sd', 14, 3)->nullable();
            $table->decimal('m5', 14, 3)->nullable();
            $table->decimal('m5_sd', 14, 3)->nullable();
            $table->decimal('m6', 14, 3)->nullable();
            $table->decimal('m6_sd', 14, 3)->nullable();
            $table->decimal('m7', 14, 3)->nullable();
            $table->decimal('m7_sd', 14, 3)->nullable();
            $table->decimal('q1', 14, 3)->nullable();
            $table->decimal('q1_sd', 14, 3)->nullable();
            $table->decimal('q2', 14, 3)->nullable();
            $table->decimal('q2_sd', 14, 3)->nullable();
            $table->decimal('q3', 14, 3)->nullable();
            $table->decimal('q3_sd', 14, 3)->nullable();
            $table->decimal('q4', 14, 3)->nullable();
            $table->decimal('q4_sd', 14, 3)->nullable();
            $table->decimal('total_output', 14, 3)->nullable();
            $table->decimal('total_output_sd', 14, 3)->nullable();
            $table->decimal('reject_output', 14, 3)->nullable();
            $table->decimal('reject_output_sd', 14, 3)->nullable();
            $table->decimal('good_output', 14, 3)->nullable();
            $table->decimal('good_output_sd', 14, 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_averages');
    }
};
