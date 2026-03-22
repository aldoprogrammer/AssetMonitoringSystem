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
        Schema::table('assets', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('assets')
            ->whereNull('uuid')
            ->orderBy('id')
            ->get()
            ->each(function (object $asset): void {
                DB::table('assets')
                    ->where('id', $asset->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        DB::statement('ALTER TABLE assets ALTER COLUMN uuid SET NOT NULL');

        Schema::table('assets', function (Blueprint $table): void {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
