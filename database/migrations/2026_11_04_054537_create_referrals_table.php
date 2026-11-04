<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Teachers have no accounts: a counselor encodes each referral received
     * through the school's existing process.
     */
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->nullable()->unique();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->date('received_on');
            $table->string('referring_teacher', 120);
            $table->string('teacher_role', 120)->nullable();
            $table->string('received_via', 20);
            $table->string('category', 30);
            $table->string('priority', 20)->default('routine');
            $table->text('reason');
            $table->string('status', 30)->default('received')->index();
            $table->text('review_outcome')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'received_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
