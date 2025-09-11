<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ContentCalendar;
use App\Livewire\ContentReview;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\Admin\TrelloIntegrationController;

Route::get('/', function () {
    return redirect('/calendar');
});

Route::get('/calendar', ContentCalendar::class)->name('calendar');
Route::get('/calendar/{role}', ContentCalendar::class)->name('calendar.role')->where('role', 'client|agency|admin');
Route::get('/calendar/review/{date}', ContentReview::class)->name('calendar.review');
Route::get('/content/add/{role}', \App\Livewire\AddContent::class)->name('content.add')->where('role', 'client|agency|admin');

Route::get('/debug', function () {
    return response()->file(public_path('../calendar-debug.html'));
});

// Client access routes with magic link middleware
Route::middleware('validate.magic.link')->group(function () {
    Route::get('/client/{token}', [ClientController::class, 'access'])->name('client.access');
    Route::get('/client/{token}/calendar', [ClientController::class, 'calendar'])->name('client.calendar');
    Route::get('/client/{token}/concept/{conceptId}', [ClientController::class, 'concept'])->name('client.concept');
    Route::get('/client/{token}/variant/{variantId}', [ClientController::class, 'variant'])->name('client.variant');
});

// Admin routes - protected by admin authentication middleware
Route::middleware('admin.auth')->group(function () {
    // Admin routes for Trello integration management
    Route::prefix('admin/trello')->name('admin.trello.')->group(function () {
        Route::get('/', [TrelloIntegrationController::class, 'index'])->name('index');
        Route::get('/create', [TrelloIntegrationController::class, 'create'])->name('create');
        Route::post('/', [TrelloIntegrationController::class, 'store'])->name('store');
        Route::get('/{integration}', [TrelloIntegrationController::class, 'show'])->name('show');
        Route::get('/{integration}/edit', [TrelloIntegrationController::class, 'edit'])->name('edit');
        Route::put('/{integration}', [TrelloIntegrationController::class, 'update'])->name('update');
        Route::delete('/{integration}', [TrelloIntegrationController::class, 'destroy'])->name('destroy');
        Route::post('/{integration}/test', [TrelloIntegrationController::class, 'testConnection'])->name('test');
        Route::post('/{integration}/sync', [TrelloIntegrationController::class, 'sync'])->name('sync');
        Route::post('/{integration}/toggle', [TrelloIntegrationController::class, 'toggle'])->name('toggle');
    });

    // Admin routes for audit log management
    Route::prefix('admin/audit')->name('admin.audit.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AuditLogController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('index');
        Route::get('/{auditLog}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('show');
        Route::get('/export', [\App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('export');
        Route::post('/cleanup', [\App\Http\Controllers\Admin\AuditLogController::class, 'cleanup'])->name('cleanup');
        Route::get('/stats', [\App\Http\Controllers\Admin\AuditLogController::class, 'stats'])->name('stats');
    });
});
