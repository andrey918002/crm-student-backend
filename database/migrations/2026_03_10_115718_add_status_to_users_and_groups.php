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
        // Обробка таблиці users
        if (!Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status')->default('active')->after('email');
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        }

        // Обробка таблиці groups
        if (!Schema::hasColumn('groups', 'status')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->string('status')->default('active')->after('name');
            });
        } else {
            Schema::table('groups', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

