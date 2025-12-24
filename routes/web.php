<?php

use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\PreviewController;
use App\Livewire\CmsPageRenderer;
use Illuminate\Support\Facades\Route;

// Contact form submission (AJAX endpoint)
Route::post('/api/contact', [ContactFormController::class, 'submit'])->name('contact.submit');

// Normal application routes

// Preview routes (admin only, can view drafts) - MUST be defined before catch-all route
Route::middleware(['auth'])->group(function () {
    Route::get('/preview/page/{page}', [PreviewController::class, 'page'])->name('preview.page');
    Route::get('/preview/post/{post}', [PreviewController::class, 'post'])->name('preview.post');
});

// Clean CMS routing - all pages handled by one route with maintenance mode check
// Maintenance middleware now handles installation checks internally
Route::middleware('maintenance.mode')->group(function () {
    Route::get('/', CmsPageRenderer::class)->defaults('slug', '/');
    // Exclude preview, admin, livewire, storage, api, and install routes
    Route::get('/{slug}', CmsPageRenderer::class)->where('slug', '^(?!preview|admin|livewire|storage|api|install).*');
});
