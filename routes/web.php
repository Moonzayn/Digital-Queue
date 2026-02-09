<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QueueController;

// USER
Route::get('/', [QueueController::class, 'index'])->name('home');
Route::get('/walk-in/{token}', [QueueController::class, 'scanLanding'])->name('scan.landing');
Route::post('/queue/walk-in', [QueueController::class, 'walkIn'])->name('queue.walkin');
Route::post('/queue/book-online', [QueueController::class, 'bookOnline'])->name('queue.book');
Route::get('/ticket/{uniqueCode}', [QueueController::class, 'showTicket'])->name('ticket.show');

// USER API
Route::get('/api/ticket/{uniqueCode}', [QueueController::class, 'ticketStatus']);
Route::post('/api/ticket/{uniqueCode}/cancel', [QueueController::class, 'cancelTicket']);
Route::get('/api/live-status', [QueueController::class, 'liveStatus']);
Route::get('/api/time-slots', [QueueController::class, 'getTimeSlots']);
Route::get('/api/queue-list', [QueueController::class, 'publicQueueList']);
// ADMIN
Route::get('/admin/login', [QueueController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin/login', [QueueController::class, 'adminAuthenticate'])->name('admin.authenticate');
Route::post('/admin/logout', [QueueController::class, 'adminLogout'])->name('admin.logout');
Route::get('/admin/dashboard', [QueueController::class, 'adminDashboard'])->name('admin.dashboard');
Route::get('/admin/qr-display', [QueueController::class, 'qrDisplayPage'])->name('admin.qr.display');

// ADMIN API
Route::get('/api/admin/data', [QueueController::class, 'adminData']);
Route::post('/api/admin/call-next', [QueueController::class, 'callNext']);
Route::post('/api/admin/skip', [QueueController::class, 'skipCurrent']);
Route::post('/api/admin/complete', [QueueController::class, 'completeCurrent']);
Route::post('/api/admin/cancel/{id}', [QueueController::class, 'adminCancelTicket']);
Route::post('/api/admin/reset', [QueueController::class, 'resetQueue']);
Route::get('/api/admin/qr-token', [QueueController::class, 'getQrToken']);
Route::post('/api/admin/qr-regenerate', [QueueController::class, 'regenerateQrToken']);
Route::get('/api/admin/settings', [QueueController::class, 'getSettings']);
Route::post('/api/admin/settings', [QueueController::class, 'updateSettings']);
