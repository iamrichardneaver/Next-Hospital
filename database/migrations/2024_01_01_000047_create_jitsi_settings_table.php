<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jitsi_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(1);
            $table->string('server_url', 255)->default('https://meet.jit.si');
            $table->string('app_id', 255)->nullable();
            $table->string('app_secret', 255)->nullable();
            $table->string('jwt_secret', 255)->nullable();
            $table->string('jwt_algorithm', 255)->default('HS256');
            $table->integer('meeting_duration_minutes')->default(60);
            $table->boolean('recording_enabled')->default(0);
            $table->boolean('chat_enabled')->default(1);
            $table->boolean('screen_sharing_enabled')->default(1);
            $table->boolean('file_sharing_enabled')->default(1);
            $table->boolean('live_streaming_enabled')->default(0);
            $table->boolean('transcription_enabled')->default(0);
            $table->boolean('waiting_room_enabled')->default(0);
            $table->boolean('mute_on_entry')->default(0);
            $table->boolean('require_display_name')->default(1);
            $table->boolean('require_password')->default(0);
            $table->boolean('enable_knocking')->default(1);
            $table->boolean('enable_lobby')->default(0);
            $table->integer('max_participants')->default(100);
            $table->string('default_language', 255)->default('en');
            $table->longText('interface_config')->nullable();
            $table->longText('config_overwrite')->nullable();
            $table->longText('toolbar_buttons')->nullable();
            $table->string('default_timezone', 255)->default('UTC');
            $table->longText('reminder_settings')->nullable();
            $table->longText('meeting_settings')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jitsi_settings');
    }
};
