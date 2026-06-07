<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Rabbanist\AdminDashboard\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Admin Dashboard Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the AdminDashboardServiceProvider with the
| configured prefix (default: /admin) and middleware stack.
|
*/

use Rabbanist\AdminDashboard\Http\Controllers\UserController;

Route::get('/', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::prefix('users')->name('users.')->group(function () {
    Route::post('/bulk', [UserController::class, 'bulkAction'])->name('bulk');
    Route::post('/stop-impersonating', [UserController::class, 'stopImpersonating'])->name('stop-impersonating');

    Route::post('/{user}/suspend', [UserController::class, 'suspend'])->name('suspend');
    Route::post('/{user}/restore', [UserController::class, 'restore'])->name('restore');
    Route::post('/{user}/photo', [UserController::class, 'updatePhoto'])->name('update-photo');
    Route::post('/{user}/impersonate', [UserController::class, 'impersonate'])->name('impersonate');
});

Route::resource('users', UserController::class);

use Rabbanist\AdminDashboard\Http\Controllers\RoleController;
use Rabbanist\AdminDashboard\Http\Controllers\PrivilegeController;

Route::get('roles/{role}/users', [RoleController::class, 'assignUsers'])->name('roles.assign-users');
Route::post('roles/{role}/users', [RoleController::class, 'syncUsers'])->name('roles.sync-users');
Route::resource('roles', RoleController::class);
Route::resource('privileges', PrivilegeController::class);

use Rabbanist\AdminDashboard\Http\Controllers\DynamicResourceController;

Route::get('resources/{resource}/export', [DynamicResourceController::class, 'export'])->name('resources.export');
Route::post('resources/{resource}/bulk', [DynamicResourceController::class, 'bulkAction'])->name('resources.bulk');
Route::post('resources/{resource}/{id}/clone', [DynamicResourceController::class, 'cloneRecord'])->name('resources.clone');

Route::get('resources/{resource}', [DynamicResourceController::class, 'index'])->name('resources.index');
Route::get('resources/{resource}/create', [DynamicResourceController::class, 'create'])->name('resources.create');
Route::post('resources/{resource}', [DynamicResourceController::class, 'store'])->name('resources.store');
Route::get('resources/{resource}/{id}', [DynamicResourceController::class, 'show'])->name('resources.show');
Route::get('resources/{resource}/{id}/edit', [DynamicResourceController::class, 'edit'])->name('resources.edit');
Route::put('resources/{resource}/{id}', [DynamicResourceController::class, 'update'])->name('resources.update');
Route::delete('resources/{resource}/{id}', [DynamicResourceController::class, 'destroy'])->name('resources.destroy');



