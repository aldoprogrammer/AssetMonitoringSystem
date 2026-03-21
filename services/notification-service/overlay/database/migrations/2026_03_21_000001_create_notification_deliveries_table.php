<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type');
            $table->string('recipient');
            $table->string('channel');
            $table->string('status');
            $table->jsonb('payload');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('event_type', 'idx_notification_event_type');
            $table->index('channel', 'idx_notification_channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
