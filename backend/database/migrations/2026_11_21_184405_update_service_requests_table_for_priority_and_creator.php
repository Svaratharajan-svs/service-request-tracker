<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Remove existing foreign key and indexes before renaming.
            $table->dropForeign(['user_id']);

            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['user_id', 'status']);

            // Rename ownership column.
            $table->renameColumn('user_id', 'created_by');

            // Add new priority column.
            $table->string('priority', 10)
                ->default('MEDIUM');

            // Apply the requested limits.
            $table->string('title', 100)->change();
            $table->string('description', 500)->change();

            // Restore foreign key and indexes.
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['created_by', 'created_at']);
            $table->index(['created_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['created_by']);

            $table->dropIndex(['created_by', 'created_at']);
            $table->dropIndex(['created_by', 'status']);

            $table->dropColumn('priority');

            $table->renameColumn('created_by', 'user_id');

            $table->string('title', 150)->change();
            $table->text('description')->change();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }
};