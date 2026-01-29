<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ReportTypeController;
use App\Http\Controllers\ReportTypeFileController;
use App\Http\Controllers\AITrainingController;
use App\Http\Controllers\ChapterController;
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

    // Rutas para gestión de capítulos de tipos de reportes
    Route::get('report-types/{reportType}/chapters', [ChapterController::class, 'index'])->name('chapters.index');
    Route::get('report-types/{reportType}/chapters/create', [ChapterController::class, 'create'])->name('chapters.create');
    Route::post('report-types/{reportType}/chapters', [ChapterController::class, 'store'])->name('chapters.store');
    Route::get('report-types/{reportType}/chapters/{chapter}/edit', [ChapterController::class, 'edit'])->name('chapters.edit');
    Route::put('report-types/{reportType}/chapters/{chapter}', [ChapterController::class, 'update'])->name('chapters.update');
    Route::delete('report-types/{reportType}/chapters/{chapter}', [ChapterController::class, 'destroy'])->name('chapters.destroy');

    // Rutas para gestión de archivos de tipos de reportes
    Route::get('report-files', [ReportTypeFileController::class, 'index'])->name('report-files.index');
    Route::get('report-files/{reportType}/prompt', [ReportTypeFileController::class, 'editPrompt'])->name('report-files.prompt');
    Route::patch('report-files/{reportType}/prompt', [ReportTypeFileController::class, 'updatePrompt'])->name('report-files.update-prompt');
    Route::get('report-files/{reportType}', [ReportTypeFileController::class, 'show'])->name('report-files.show');
    Route::get('report-files/{reportType}/create', [ReportTypeFileController::class, 'create'])->name('report-files.create');
    Route::post('report-files/{reportType}', [ReportTypeFileController::class, 'store'])->name('report-files.store');
    Route::get('report-files/file/{file}/download', [ReportTypeFileController::class, 'download'])->name('report-files.download');
    Route::delete('report-files/file/{file}', [ReportTypeFileController::class, 'destroy'])->name('report-files.destroy');

    // Rutas para Entrenamiento de IA
    Route::get('ai-training', [AITrainingController::class, 'index'])->name('ai-training.index');
    Route::get('ai-training/{reportType}', [AITrainingController::class, 'show'])->name('ai-training.show');
    Route::post('ai-training/{reportType}/train', [AITrainingController::class, 'train'])->name('ai-training.train');

    // Rutas para Generación con IA
    Route::get('ai-training/{reportType}/generate', [AITrainingController::class, 'createGeneration'])->name('ai-training.generate.create');
    Route::post('ai-training/{reportType}/generate', [AITrainingController::class, 'generate'])->name('ai-training.generate.store');
    Route::get('ai-training/{reportType}/generations', [AITrainingController::class, 'generations'])->name('ai-training.generations');
    Route::get('ai-training/{reportType}/generation/{generation}', [AITrainingController::class, 'showGeneration'])->name('ai-training.generation.show');
    Route::get('ai-training/{reportType}/generation/{generation}/download', [AITrainingController::class, 'downloadGeneration'])->name('ai-training.generation.download');
    Route::delete('ai-training/{reportType}/generation/{generation}', [AITrainingController::class, 'destroyGeneration'])->name('ai-training.generation.destroy');
});

require __DIR__.'/auth.php';
