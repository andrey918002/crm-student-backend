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
        // 1. Таблиця платежів (прив'язана до student_id)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Явно вказуємо таблицю 'students', щоб уникнути помилок іменування
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at');
            $table->timestamps();
        });

        // 2. Таблиця відвідуваності (прив'язана до student_id та group_id)
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->date('lesson_date');
            $table->boolean('is_present')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('payments');
    }
};
