<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Temporary page to preview the design system (removed in part 3)
Route::view('/design-test', 'design-test');
