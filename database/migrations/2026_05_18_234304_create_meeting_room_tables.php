<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('peer_id', 80)->unique();
            $table->string('display_name');
            $table->boolean('camera_enabled')->default(false);
            $table->boolean('microphone_enabled')->default(false);
            $table->boolean('screen_enabled')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['meeting_id', 'last_seen_at']);
        });

        Schema::create('meeting_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('sender_peer_id', 80);
            $table->string('receiver_peer_id', 80);
            $table->string('type', 40);
            $table->json('payload');
            $table->timestamps();
            $table->index(['meeting_id', 'receiver_peer_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_signals');
        Schema::dropIfExists('meeting_participants');
    }
};
