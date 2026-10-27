<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request) => $request->user()
    ? to_route('panel')
    : to_route('public.dashboard'))->name('home');

require __DIR__.'/barbershop.php';
require __DIR__.'/settings.php';
