<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GmailController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/connect-gmail', [GmailController :: class, 'connectGmail']);
Route::get('/auth/google/callback', [GmailController::class, 'callback']);
