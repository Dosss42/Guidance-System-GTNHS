<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A guidance case is an administrative record of a support process. It is
     * not a diagnosis or a disciplinary finding. Cases are shared by all
     * counselors, so there is deliberately no assigned_counselor_id.
     */
    public function up(): void
    {
        Schema::create('guidance_cases', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->nullable()->unique();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('referral_id')->nullable()->unique()->constrained('referrals')->restrictOnDelete();
            $table->string('category', 30);
            $table->string('status', 20)->default('open')->index();
            $table->date('opened_on');
            $table->text('summary')->nullable();
            $table->date('closed_on')->nullable();
            $table->text('closure_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guidance_cases');
    }
};
