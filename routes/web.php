<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


use App\Http\Controllers\PaymentController;



Route::get('/payment', [PaymentController::class, 'index']);
Route::post('/payment/process', [PaymentController::class, 'process']);


