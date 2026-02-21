<?php

use Illuminate\Support\Facades\Route;
use App\Jobs\ProcessPayment;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/generate-jobs', function () {

    for ($i = 0; $i < 15; $i++) {
        ProcessPayment::dispatch();
    }

    return "15 jobs dispatched.";
});