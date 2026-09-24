<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\{AuthController,BookingController,EventController,AdminBookingController};

Route::get('/', function () {
    $events = Schema::hasTable('events') ? \App\Models\Event::latest()->take(6)->get() : collect();
    return view('welcome', compact('events'));
});
Route::view('/scoring', 'scoring')->name('scoring');
Route::get('/api/bookings', [BookingController::class,'availability']);
Route::post('/bookings', [BookingController::class,'store'])->name('bookings.store');
Route::get('/login',[AuthController::class,'showLogin'])->name('login');
Route::post('/login',[AuthController::class,'login']);
Route::get('/register',[AuthController::class,'showRegister'])->name('register');
Route::post('/register',[AuthController::class,'register']);
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::get('/events/{event}',[EventController::class,'show'])->name('events.show');
Route::post('/events/{event}/register',[EventController::class,'register'])->middleware('auth')->name('events.register');
Route::patch('/events/{event}/matches/{match}/score',[EventController::class,'score'])->name('events.matches.score');
Route::post('/events/{event}/matches/randomize',[EventController::class,'randomize'])->name('events.matches.randomize');
Route::middleware(['auth','admin'])->prefix('admin')->name('admin.')->group(function(){
 Route::get('/', fn() => redirect()->route('admin.events.index'))->name('dashboard');
 Route::resource('events',EventController::class)->except(['show']);
 Route::get('bookings',[AdminBookingController::class,'index'])->name('bookings.index');
 Route::patch('bookings/{booking}',[AdminBookingController::class,'update'])->name('bookings.update');
 Route::delete('bookings/{booking}',[AdminBookingController::class,'destroy'])->name('bookings.destroy');
 Route::post('events/{event}/matches/randomize',[EventController::class,'randomize'])->name('events.matches.randomize');
});

Route::get('/dashboard', function () {
    if (auth()->user()->is_admin) {
        return redirect()->route('admin.dashboard');
    }

    $events = \App\Models\Event::query()->latest()->take(6)->get();
    $registeredEvents = auth()->user()->events()->latest('date')->take(6)->get();

    return view('player.dashboard', compact('events', 'registeredEvents'));
})->middleware(['auth'])->name('dashboard');
