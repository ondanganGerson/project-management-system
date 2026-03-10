<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app'     => config('app.name'),
        'version' => config('app.version', 'v1'),
        'status'  => 'running',
    ]);
});
