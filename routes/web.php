<?php

use App\Http\Controllers\AdminBookingController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PlayerDashboardController;
use App\Models\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $events = Schema::hasTable('events')
        ? Event::upcoming()->orderBy('date')->orderBy('time')->take(6)->get()
        : collect();

    return view('welcome', compact('events'));
});

Route::view('/scoring', 'scoring')->name('scoring');

/*
|--------------------------------------------------------------------------
| Court bookings
|--------------------------------------------------------------------------
*/

Route::get('/api/bookings', [BookingController::class, 'availability']);
Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Events and live scoring
|--------------------------------------------------------------------------
*/

Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::post('/events/{event}/register', [EventController::class, 'register'])
    ->middleware('auth')
    ->name('events.register');
Route::patch('/events/{event}/matches/{match}/score', [EventController::class, 'score'])
    ->name('events.matches.score');

/*
|--------------------------------------------------------------------------
| Player dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', PlayerDashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        // Events ("history" must come before the resource's {event} routes).
        Route::get('events/history', [EventController::class, 'history'])->name('events.history');
        Route::resource('events', EventController::class)->except(['show']);
        Route::post('events/{event}/matches/randomize', [EventController::class, 'randomize'])
            ->name('events.matches.randomize');

        // Bookings
        Route::get('bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::patch('bookings/{booking}', [AdminBookingController::class, 'update'])->name('bookings.update');
        Route::delete('bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');
    });
