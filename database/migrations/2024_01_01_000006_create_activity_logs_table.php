<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('method', 10);
            $table->string('endpoint', 500);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->integer('response_status')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
