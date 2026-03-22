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
        DB::statement('ALTER TABLE user_projections ALTER COLUMN external_user_id TYPE varchar(36) USING external_user_id::text');
        DB::statement('ALTER TABLE user_projections ALTER COLUMN employee_id TYPE varchar(36) USING employee_id::text');
        DB::statement('ALTER TABLE assignments ALTER COLUMN user_id TYPE varchar(36) USING user_id::text');

        Schema::table('assignments', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('assignments')
            ->whereNull('uuid')
            ->orderBy('id')
            ->get()
            ->each(function (object $assignment): void {
                DB::table('assignments')
                    ->where('id', $assignment->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        DB::statement('ALTER TABLE assignments ALTER COLUMN uuid SET NOT NULL');

        Schema::table('assignments', function (Blueprint $table): void {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });

        DB::statement('ALTER TABLE assignments ALTER COLUMN user_id TYPE bigint USING user_id::bigint');
        DB::statement('ALTER TABLE user_projections ALTER COLUMN employee_id TYPE bigint USING NULLIF(employee_id, \'\')::bigint');
        DB::statement('ALTER TABLE user_projections ALTER COLUMN external_user_id TYPE bigint USING external_user_id::bigint');
    }
};
