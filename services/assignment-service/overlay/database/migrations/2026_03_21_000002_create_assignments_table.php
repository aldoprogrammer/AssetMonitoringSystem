<?php

use App\Models\Assignment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_email');
            $table->string('asset_serial_number');
            $table->enum('status', [Assignment::STATUS_CHECKED_OUT, Assignment::STATUS_CHECKED_IN]);
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->index(['asset_serial_number', 'status'], 'idx_assignments_asset_status');
            $table->index('user_id', 'idx_assignments_user_id');
        });

        DB::statement("CREATE UNIQUE INDEX idx_assignments_active_asset ON assignments (asset_serial_number) WHERE status = 'checked_out'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_assignments_active_asset');
        Schema::dropIfExists('assignments');
    }
};
