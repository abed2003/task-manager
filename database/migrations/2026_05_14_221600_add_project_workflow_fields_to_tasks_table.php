<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['todo', 'in_progress', 'review', 'completed'])->default('todo')->after('description');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->after('status');
            $table->date('due_at')->nullable()->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['status', 'priority', 'due_at']);
        });
    }
};
