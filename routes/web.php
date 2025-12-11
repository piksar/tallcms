<?php

use App\Http\Controllers\CmsPageController;
use App\Livewire\CmsPageRenderer;
use Illuminate\Support\Facades\Route;

// Admin pages list (for admin panel navigation)
Route::get('/pages', [CmsPageController::class, 'index'])->name('cms.pages.index');

// Clean CMS routing - all pages handled by one route with maintenance mode check
Route::middleware('maintenance.mode')->group(function () {
    Route::get('/', CmsPageRenderer::class)->defaults('slug', '/');
    Route::get('/{slug}', CmsPageRenderer::class)->where('slug', '.*');
});
