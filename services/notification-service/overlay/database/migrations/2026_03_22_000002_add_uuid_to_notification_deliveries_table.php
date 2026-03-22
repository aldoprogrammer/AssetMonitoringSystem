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
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('notification_deliveries')
            ->whereNull('uuid')
            ->orderBy('id')
            ->get()
            ->each(function (object $delivery): void {
                DB::table('notification_deliveries')
                    ->where('id', $delivery->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        DB::statement('ALTER TABLE notification_deliveries ALTER COLUMN uuid SET NOT NULL');

        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
