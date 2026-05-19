<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE projects MODIFY status ENUM('active', 'on_hold', 'completed', 'review') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::table('projects')->where('status', 'review')->update(['status' => 'active']);
        DB::statement("ALTER TABLE projects MODIFY status ENUM('active', 'on_hold', 'completed') NOT NULL DEFAULT 'active'");
    }
};
