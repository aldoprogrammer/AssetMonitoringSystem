<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_projections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('external_user_id')->unique();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_projections');
    }
};
