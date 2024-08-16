<?php

use Illuminate\Support\Facades\Route;


Route::get('/epay/webhook', [App\Extensions\Gateways\Epay\Epay::class, 'webhook'])->name('epay.webhook');
