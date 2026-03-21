<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('consumer');
            $table->string('message_id');
            $table->timestamp('processed_at');

            $table->unique(['consumer', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_messages');
    }
};
