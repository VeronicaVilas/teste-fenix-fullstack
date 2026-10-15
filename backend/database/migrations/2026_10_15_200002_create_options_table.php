<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('text', 500);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['question_id', 'position']);
        });

        DB::statement('CREATE UNIQUE INDEX options_one_correct_per_question ON options (question_id) WHERE is_correct = true');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS options_one_correct_per_question');

        Schema::dropIfExists('options');
    }
};
