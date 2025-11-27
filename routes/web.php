<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QrValidationController;

// Panel administrativo (protegido por middleware de autenticación/admin)
// middleware(['auth', 'can:admin-panel'])->
Route::prefix('adminqr')->name('adminqr.')->group(function () {
    Route::get('/', [\App\Http\Controllers\AdminQr\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [\App\Http\Controllers\AdminQr\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/filter', [\App\Http\Controllers\AdminQr\DashboardController::class, 'filter'])->name('dashboard.filter');
    Route::get('/dashboard/compare', [\App\Http\Controllers\AdminQr\DashboardController::class, 'compare'])->name('dashboard.compare');
    Route::get('/metrics', [\App\Http\Controllers\AdminQr\MetricsController::class, 'index'])->name('metrics.index');
    Route::get('/metrics/export', [\App\Http\Controllers\AdminQr\MetricsController::class, 'export'])->name('metrics.export');
    Route::resource('merchants', \App\Http\Controllers\AdminQr\MerchantController::class); 
    Route::get('/merchants/{merchant}/download-qr', [\App\Http\Controllers\AdminQr\MerchantController::class, 'downloadQr'])->name('merchants.downloadQr');
    Route::get('/merchants/ajax/list', [\App\Http\Controllers\AdminQr\MerchantController::class, 'ajaxList'])->name('merchants.ajax.list');

});

// Muestra la vista del formulario
Route::get('qr/validate/{token}', [\App\Http\Controllers\QrValidationController::class, 'show'])->name('qr.validate');

// Procesa el formulario y aplica el beneficio
Route::post('qr/validate/{token}/benefit', [\App\Http\Controllers\QrValidationController::class, 'applyBenefit'])->name('qr.apply_benefit');
Route::get('/qr/benefit/download/{metricId}', [QrValidationController::class, 'downloadBenefitPDF'])->name('qr.download_benefit_pdf');
Route::get('/qr/benefit/{metricId}/{token}', [QrValidationController::class, 'showBenefitPage'])->name('qr.benefit_applied');

// Metodo de busqueda en validación
Route::get('/api/ally/find-by-nit', [\App\Http\Controllers\QrValidationController::class, 'findAllyByNit'])
    ->name('api.ally.findByNit');
