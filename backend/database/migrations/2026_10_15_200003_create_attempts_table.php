<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('total_questions');
            $table->decimal('percentage', 5, 2);
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['exam_id', 'percentage']);
            $table->index('percentage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
