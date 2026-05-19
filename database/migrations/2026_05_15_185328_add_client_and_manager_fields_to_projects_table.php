<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('client_name')->nullable()->after('description');
            $table->foreignId('manager_id')->nullable()->after('client_name')->constrained('users')->nullOnDelete();
        });

        DB::table('projects')
            ->whereNull('manager_id')
            ->whereNotNull('created_by')
            ->update(['manager_id' => DB::raw('created_by')]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn('client_name');
        });
    }
};
