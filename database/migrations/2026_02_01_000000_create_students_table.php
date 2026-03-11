<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // ПІБ учня
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('active'); // active / inactive (оплатив чи ні)
            $table->text('additional_info')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
