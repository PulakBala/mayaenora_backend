<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback storage handler for environments without native symlink or missing storage:link
Route::get('/storage/{path}', function (string $path) {
    $filePath = storage_path('app/public/' . $path);

    if (! file_exists($filePath)) {
        abort(404);
    }

    $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');
