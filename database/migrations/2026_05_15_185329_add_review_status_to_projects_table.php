<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE projects MODIFY status ENUM('active', 'on_hold', 'completed', 'review') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::table('projects')->where('status', 'review')->update(['status' => 'active']);

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('status')->default('active')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE projects MODIFY status ENUM('active', 'on_hold', 'completed') NOT NULL DEFAULT 'active'");
    }
};
