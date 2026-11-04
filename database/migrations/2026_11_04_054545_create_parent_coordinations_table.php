<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Parents and guardians have no accounts. Collect only what the
     * approved school process needs.
     */
    public function up(): void
    {
        Schema::create('parent_coordinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('guidance_case_id')->nullable()->constrained('guidance_cases')->restrictOnDelete();
            $table->dateTime('coordinated_at');
            $table->string('contact_name', 120);
            $table->string('relationship', 40);
            $table->string('method', 20);
            $table->string('purpose');
            $table->text('outcome')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'coordinated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_coordinations');
    }
};
