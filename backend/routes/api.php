
<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Service Request Tracker API is running',
        'application' => config('app.name'),
        'timestamp' => now()->toISOString(),
    ]);
});
