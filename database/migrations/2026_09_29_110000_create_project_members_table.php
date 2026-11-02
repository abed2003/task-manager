<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // Existing assignees already work on these projects, so keep their access.
        $now = now();
        $rows = DB::table('tasks')
            ->whereNotNull('project_id')
            ->select('project_id', 'user_id')
            ->distinct()
            ->get()
            ->map(fn ($row) => [
                'project_id' => $row->project_id,
                'user_id' => $row->user_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('project_members')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
