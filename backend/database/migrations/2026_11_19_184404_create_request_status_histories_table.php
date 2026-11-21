<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_request_id')
                ->constrained('service_requests')
                ->cascadeOnDelete();

            $table->foreignId('changed_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);

            $table->unsignedInteger('version');

            // History is append-only; no updated_at column.
            $table->timestamp('created_at')->useCurrent();

            $table->index([
                'service_request_id',
                'version',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_status_histories');
    }
};