<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_heartbeats', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('device_heartbeats')
            ->whereNull('uuid')
            ->orderBy('id')
            ->get()
            ->each(function (object $heartbeat): void {
                DB::table('device_heartbeats')
                    ->where('id', $heartbeat->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        DB::statement('ALTER TABLE device_heartbeats ALTER COLUMN uuid SET NOT NULL');

        Schema::table('device_heartbeats', function (Blueprint $table): void {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('device_heartbeats', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
