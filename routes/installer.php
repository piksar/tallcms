<?php

use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installer Routes
|--------------------------------------------------------------------------
|
| These routes handle the TallCMS web installation process.
| They work without requiring a complete .env file.
|
*/

// Installer routes with minimal middleware (works without full .env)
Route::prefix('install')->name('installer.')->group(function () {
    
    // Welcome page - no middleware needed
    Route::get('/', [InstallerController::class, 'welcome'])->name('welcome');
    
    // Environment check - minimal middleware
    Route::get('/environment', [InstallerController::class, 'environment'])
        ->middleware(['installer.gate'])
        ->name('environment');
    
    // Configuration - add throttling
    Route::get('/configuration', [InstallerController::class, 'configuration'])
        ->middleware(['installer.gate'])
        ->name('configuration');
        
    Route::post('/configuration', [InstallerController::class, 'install'])
        ->middleware(['installer.gate', 'throttle:20,1'])
        ->name('install');
    
    // Database test (AJAX) - add throttling
    Route::post('/test-database', [InstallerController::class, 'testDatabase'])
        ->middleware(['installer.gate', 'throttle:60,1'])
        ->name('test-database');
    
    // Installation complete - minimal middleware
    Route::get('/complete', [InstallerController::class, 'complete'])
        ->name('complete');
    
});