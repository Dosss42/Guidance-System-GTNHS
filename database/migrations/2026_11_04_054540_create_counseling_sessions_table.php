<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Notes and next steps are stored encrypted (TEXT, because ciphertext
     * length varies) and are only sent to the browser when a counselor
     * unseals them, which is written to the audit log.
     */
    public function up(): void
    {
        Schema::create('counseling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guidance_case_id')->constrained('guidance_cases')->restrictOnDelete();
            $table->dateTime('session_at');
            $table->string('session_type', 30);
            $table->text('confidential_notes');
            $table->text('next_steps')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['guidance_case_id', 'session_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counseling_sessions');
    }
};
