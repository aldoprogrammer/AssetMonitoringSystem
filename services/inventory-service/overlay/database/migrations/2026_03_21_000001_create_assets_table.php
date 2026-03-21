<?php

use App\Models\Asset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('asset_tag')->unique();
            $table->jsonb('specs');
            $table->enum('status', [
                Asset::STATUS_AVAILABLE,
                Asset::STATUS_ASSIGNED,
                Asset::STATUS_MAINTENANCE,
                Asset::STATUS_RETIRED,
            ])->default(Asset::STATUS_AVAILABLE);
            $table->timestamps();

            $table->index('serial_number', 'idx_assets_serial_number');
            $table->index('status', 'idx_assets_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
