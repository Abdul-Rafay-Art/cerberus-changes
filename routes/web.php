<?php

use App\Http\Controllers\InspectionController;
use App\Http\Controllers\InspectionPhotoController;
use App\Http\Controllers\InspectionVideoController;
use App\Http\Controllers\PackageCatalogController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffBookingController;
use App\Http\Controllers\StaffUserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/book-inspection')->name('home');
Route::get('/book-inspection', [PublicBookingController::class, 'create'])->name('bookings.create');
Route::post('/book-inspection', [PublicBookingController::class, 'store'])->middleware('throttle:5,1')->name('bookings.store');
Route::get('/api/packages', [PackageCatalogController::class, 'index'])->name('api.packages.index');
Route::get('/api/packages/{packageId}', [PackageCatalogController::class, 'show'])->whereNumber('packageId')->name('api.packages.show');
Route::post('/api/packages/quote', [PackageCatalogController::class, 'quote'])->middleware('throttle:30,1')->name('api.packages.quote');
Route::get('/booking-received/{bookingReference}', [PublicBookingController::class, 'received'])->name('bookings.received');
Route::get('/r/{token}', [ReportController::class, 'show'])->middleware('throttle:60,1')->name('customer-reports.show');
Route::get('/r/{token}/pdf', [ReportController::class, 'download'])->middleware('throttle:30,1')->name('customer-reports.download');

Route::get('/staff/login', [StaffAuthController::class, 'create'])->name('staff.login');
Route::post('/staff/login', [StaffAuthController::class, 'store'])->middleware('throttle:5,1')->name('staff.login.store');
Route::middleware(['auth', 'staff.active'])->prefix('staff')->name('staff.')->group(function (): void {
    Route::post('/logout', [StaffAuthController::class, 'destroy'])->name('logout');
    Route::get('/bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [StaffBookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [StaffBookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/export', [StaffBookingController::class, 'export'])->name('bookings.export');
    Route::get('/bookings/{booking}/edit', [StaffBookingController::class, 'edit'])->name('bookings.edit');
    Route::put('/bookings/{booking}/details', [StaffBookingController::class, 'updateDetails'])->name('bookings.update-details');
    Route::get('/bookings/{booking}', [StaffBookingController::class, 'show'])->name('bookings.show');
    Route::put('/bookings/{booking}', [StaffBookingController::class, 'update'])->name('bookings.update');
    Route::post('/bookings/{booking}/start-inspection', [InspectionController::class, 'start'])->name('bookings.start-inspection');
    Route::get('/inspections/{inspection}', [InspectionController::class, 'show'])->name('inspections.show');
    Route::put('/inspection-checks/{check}', [InspectionController::class, 'saveCheck'])->middleware('throttle:120,1')->name('inspection-checks.update');
    Route::post('/inspections/{inspection}/complete', [InspectionController::class, 'complete'])->name('inspections.complete');
    Route::post('/inspections/{inspection}/photos', [InspectionPhotoController::class, 'store'])->name('inspections.photos.store');
    Route::post('/inspections/{inspection}/photos/reorder', [InspectionPhotoController::class, 'reorder'])->name('inspections.photos.reorder');
    Route::get('/inspection-photos/{photo}', [InspectionPhotoController::class, 'show'])->name('inspection-photos.show');
    Route::put('/inspection-photos/{photo}', [InspectionPhotoController::class, 'update'])->name('inspection-photos.update');
    Route::delete('/inspection-photos/{photo}', [InspectionPhotoController::class, 'destroy'])->name('inspection-photos.destroy');
    Route::put('/inspections/{inspection}/video', [InspectionVideoController::class, 'store'])->name('inspections.video.store');
    Route::delete('/inspections/{inspection}/video', [InspectionVideoController::class, 'destroy'])->name('inspections.video.destroy');
    Route::post('/reports/{report}/revoke', [ReportController::class, 'revoke'])->name('reports.revoke');
    Route::post('/inspections/{inspection}/reports', [ReportController::class, 'generateVersion'])->middleware('throttle:5,1')->name('inspections.reports.generate-version');
    Route::post('/reports/{report}/customer', [ReportController::class, 'customer'])->name('reports.customer');
    Route::middleware('staff.admin')->prefix('users')->name('users.')->group(function (): void {
        Route::get('/', [StaffUserController::class, 'index'])->name('index');
        Route::get('/create', [StaffUserController::class, 'create'])->name('create');
        Route::post('/', [StaffUserController::class, 'store'])->name('store');
        Route::post('/{staffUser}/toggle', [StaffUserController::class, 'toggle'])->name('toggle');
        Route::put('/{staffUser}/role', [StaffUserController::class, 'updateRole'])->name('role.update');
        Route::delete('/{staffUser}', [StaffUserController::class, 'destroy'])->name('destroy');
    });
    Route::middleware('staff.admin')->group(function (): void {
        Route::get('/reports/{report}/preview', [ReportController::class, 'preview'])->name('reports.preview');
    });
});
