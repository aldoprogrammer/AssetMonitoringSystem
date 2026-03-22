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
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('audit_logs')
            ->whereNull('uuid')
            ->orderBy('id')
            ->get()
            ->each(function (object $auditLog): void {
                DB::table('audit_logs')
                    ->where('id', $auditLog->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        DB::statement('ALTER TABLE audit_logs ALTER COLUMN uuid SET NOT NULL');

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
