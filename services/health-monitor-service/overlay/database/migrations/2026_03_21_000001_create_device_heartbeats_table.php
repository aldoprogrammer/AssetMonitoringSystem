<?php

use App\Models\DeviceHeartbeat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_heartbeats', function (Blueprint $table): void {
            $table->id();
            $table->string('device_serial_number')->unique();
            $table->string('status')->default(DeviceHeartbeat::STATUS_ONLINE);
            $table->jsonb('metadata')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_device_heartbeats_status');
            $table->index('last_seen_at', 'idx_device_heartbeats_last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_heartbeats');
    }
};
