<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Doctor;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

// Redirect root
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'doctor.dashboard');
    }
    return redirect()->route('login');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post')
        ->middleware('throttle:10,1');
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Public form routes
Route::name('forms.')->group(function () {
    Route::get('/forms/{form}/public', [PublicFormController::class, 'show'])->name('public.show');
    Route::post('/forms/{form}/public', [PublicFormController::class, 'submit'])->name('public.submit');
});

// Admin routes
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Doctors
        Route::resource('doctors', Admin\DoctorController::class);
        Route::patch('doctors/{doctor}/toggle-status', [Admin\DoctorController::class, 'toggleStatus'])->name('doctors.toggle-status');

        // Forms
        Route::resource('forms', Admin\FormController::class);
        Route::patch('forms/{form}/publish', [Admin\FormController::class, 'publish'])->name('forms.publish');
        Route::patch('forms/{form}/unpublish', [Admin\FormController::class, 'unpublish'])->name('forms.unpublish');
        Route::post('forms/{form}/duplicate', [Admin\FormController::class, 'duplicate'])->name('forms.duplicate');
        Route::get('forms/{form}/preview', [Admin\FormController::class, 'preview'])->name('forms.preview');

        // Form Builder
        Route::get('forms/{form}/builder', [Admin\FormBuilderController::class, 'show'])->name('forms.builder');
        Route::post('forms/{form}/builder/save', [Admin\FormBuilderController::class, 'save'])->name('forms.builder.save');
        Route::post('forms/{form}/builder/field', [Admin\FormBuilderController::class, 'addField'])->name('forms.builder.add-field');
        Route::delete('forms/{form}/builder/field/{field}', [Admin\FormBuilderController::class, 'deleteField'])->name('forms.builder.delete-field');
        Route::post('forms/{form}/builder/reorder', [Admin\FormBuilderController::class, 'reorderFields'])->name('forms.builder.reorder');

        // Form Assignments
        Route::get('forms/{form}/assign', [Admin\FormAssignmentController::class, 'show'])->name('forms.assign');
        Route::post('forms/{form}/assign', [Admin\FormAssignmentController::class, 'update'])->name('forms.assign.update');

        // Responses
        Route::get('/responses', [Admin\ResponseController::class, 'index'])->name('responses.index');
        Route::get('/responses/{response}', [Admin\ResponseController::class, 'show'])->name('responses.show');
        Route::delete('/responses/{response}', [Admin\ResponseController::class, 'destroy'])->name('responses.destroy');
        Route::get('/forms/{form}/responses/export', [Admin\ResponseController::class, 'export'])->name('forms.responses.export');
    });

// Doctor routes
Route::middleware(['auth', 'doctor.role'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {
        Route::get('/dashboard', [Doctor\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/forms', [Doctor\FormController::class, 'index'])->name('forms.index');
        Route::get('/forms/{form}', [Doctor\FormController::class, 'show'])->name('forms.show');

        Route::get('/responses', [Doctor\ResponseController::class, 'index'])->name('responses.index');
        Route::get('/responses/{response}', [Doctor\ResponseController::class, 'show'])->name('responses.show');
        Route::get('/forms/{form}/responses/export', [Doctor\ResponseController::class, 'export'])->name('forms.responses.export');
    });
