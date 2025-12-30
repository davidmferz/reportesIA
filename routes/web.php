<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ReportTypeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    // Si el usuario está autenticado, redirigir al dashboard
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    // Si no está autenticado, redirigir al login
    return redirect('/login');
});

Route::get('/dashboard', function () {
    return view('crm.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRM Profile Settings with tabs
    Route::get('/crm/profile/settings', function () {
        return view('crm.profile.settings');
    })->name('crm.profile.settings');
});

// Rutas de administración
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('report-types', ReportTypeController::class);
});

require __DIR__.'/auth.php';
