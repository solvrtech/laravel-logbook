<?php

use Illuminate\Support\Facades\Route;
use Solvrtech\Logbook\Controller\LogbookHealthController;

Route::middleware('logbook')
    ->get('/logbook-health', LogbookHealthController::class);
