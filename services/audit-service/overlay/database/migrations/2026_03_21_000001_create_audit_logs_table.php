<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type');
            $table->string('routing_key');
            $table->string('source_service');
            $table->jsonb('payload');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index('routing_key', 'idx_audit_logs_routing_key');
            $table->index('occurred_at', 'idx_audit_logs_occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
