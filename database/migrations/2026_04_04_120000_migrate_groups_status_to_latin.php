<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('status', 32)->default('active')->change();
        });

        DB::table('groups')->where('status', 'Набір')->update(['status' => 'pending']);
        DB::table('groups')->where('status', 'Активна')->update(['status' => 'active']);
        DB::table('groups')->where('status', 'Завершена')->update(['status' => 'finished']);
    }

    public function down(): void
    {
        DB::table('groups')->where('status', 'pending')->update(['status' => 'Набір']);
        DB::table('groups')->where('status', 'active')->update(['status' => 'Активна']);
        DB::table('groups')->where('status', 'finished')->update(['status' => 'Завершена']);
    }
};
