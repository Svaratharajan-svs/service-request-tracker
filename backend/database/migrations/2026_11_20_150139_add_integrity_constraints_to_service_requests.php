<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
public function up(): void
{
    DB::statement("
        ALTER TABLE service_requests
        ADD CONSTRAINT service_requests_status_check
        CHECK (status IN ('OPEN', 'IN_PROGRESS', 'RESOLVED'))
    ");

    DB::statement("
        ALTER TABLE service_requests
        ADD CONSTRAINT service_requests_version_check
        CHECK (version >= 1)
    ");

    DB::statement("
        ALTER TABLE request_status_histories
        ADD CONSTRAINT request_histories_version_check
        CHECK (version >= 1)
    ");
}

public function down(): void
{
    DB::statement("
        ALTER TABLE service_requests
        DROP CONSTRAINT IF EXISTS service_requests_status_check
    ");

    DB::statement("
        ALTER TABLE service_requests
        DROP CONSTRAINT IF EXISTS service_requests_version_check
    ");

    DB::statement("
        ALTER TABLE request_status_histories
        DROP CONSTRAINT IF EXISTS request_histories_version_check
    ");
}
};
