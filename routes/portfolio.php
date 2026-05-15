<?php

use App\Http\Controllers\Construction\PortfolioProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('portfolio/projects', [PortfolioProjectController::class, 'index']);
});
