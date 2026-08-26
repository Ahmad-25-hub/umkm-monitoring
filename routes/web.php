<?php

use App\Http\Controllers\OwnerOverviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', OwnerOverviewController::class)->name('overview');
