<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirect old ops path to admin
Route::redirect('/ops', '/admin', 301);
