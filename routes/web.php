<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/agendar');

Route::get('/agendar', [BookingController::class, 'create'])->name('booking.create');
Route::get('/agendar/horarios', [BookingController::class, 'slots'])->name('booking.slots');
Route::post('/agendar', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('booking.store');
Route::get('/agendamento/{token}', [BookingController::class, 'show'])->name('booking.show');
Route::post('/agendamento/{token}/cancelar', [BookingController::class, 'cancel'])
    ->middleware('throttle:10,1')
    ->name('booking.cancel');
