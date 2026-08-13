<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rentame API Routes — v1
|--------------------------------------------------------------------------
|
| Convenciones:
| - Todas las rutas bajo /api/v1/ (configurado en bootstrap/app.php)
| - Autenticación: Laravel Sanctum (Bearer Token)
| - Respuesta siempre en JSON con envoltorio estándar
|
*/

Route::prefix('v1')->name('v1.')->group(function () {

    // ----------------------------------------------------------------
    // Rutas PÚBLICAS — No requieren autenticación
    // ----------------------------------------------------------------
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [\App\Http\Controllers\Api\V1\AuthController::class, 'register'])
            ->name('register');

        Route::post('login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');
    });

    // Planes disponibles (público — para mostrar en landing)
    Route::get('plans', [\App\Http\Controllers\Api\V1\PlanController::class, 'index'])
        ->name('plans.index');

    // ----------------------------------------------------------------
    // Rutas PROTEGIDAS — Requieren token Sanctum válido
    // ----------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout'])
                ->name('logout');
            Route::get('me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me'])
                ->name('me');
            Route::patch('profile', [\App\Http\Controllers\Api\V1\AuthController::class, 'updateProfile'])
                ->name('profile.update');
            Route::post('change-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'changePassword'])
                ->name('password.change');
        });

        // Dashboard
        Route::get('dashboard', [\App\Http\Controllers\Api\V1\DashboardController::class, 'index'])
            ->name('dashboard');

        // Plan del usuario
        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('current', [\App\Http\Controllers\Api\V1\PlanController::class, 'current'])
                ->name('current');
            Route::post('subscribe', [\App\Http\Controllers\Api\V1\PlanController::class, 'subscribe'])
                ->name('subscribe');
        });

        // Categorías de activos
        Route::apiResource('asset-categories', \App\Http\Controllers\Api\V1\AssetCategoryController::class);

        // Activos
        Route::apiResource('assets', \App\Http\Controllers\Api\V1\AssetController::class);
        Route::post('assets/{asset}/photos', [\App\Http\Controllers\Api\V1\AssetController::class, 'uploadPhoto'])
            ->name('assets.photos');

        // Clientes
        Route::apiResource('customers', \App\Http\Controllers\Api\V1\CustomerController::class);
        Route::get('customers/{customer}/rentals', [\App\Http\Controllers\Api\V1\CustomerController::class, 'rentals'])
            ->name('customers.rentals');
        Route::get('customers/{customer}/statement', [\App\Http\Controllers\Api\V1\CustomerController::class, 'statement'])
            ->name('customers.statement');

        // Servicios extras
        Route::apiResource('extra-services', \App\Http\Controllers\Api\V1\ExtraServiceController::class);

        // Rentas
        Route::apiResource('rentals', \App\Http\Controllers\Api\V1\RentalController::class);
        Route::post('rentals/{rental}/complete', [\App\Http\Controllers\Api\V1\RentalController::class, 'complete'])
            ->name('rentals.complete');
        Route::post('rentals/{rental}/cancel', [\App\Http\Controllers\Api\V1\RentalController::class, 'cancel'])
            ->name('rentals.cancel');
        Route::get('rentals/{rental}/contract-pdf', [\App\Http\Controllers\Api\V1\RentalController::class, 'contractPdf'])
            ->name('rentals.contract-pdf');

        // Pagos
        Route::apiResource('payments', \App\Http\Controllers\Api\V1\PaymentController::class)
            ->except(['update']);
        Route::get('payments/summary', [\App\Http\Controllers\Api\V1\PaymentController::class, 'summary'])
            ->name('payments.summary');
        Route::get('payments/{payment}/receipt-pdf', [\App\Http\Controllers\Api\V1\PaymentController::class, 'receiptPdf'])
            ->name('payments.receipt');

        // Egresos / Mantenimientos
        Route::apiResource('expenses', \App\Http\Controllers\Api\V1\ExpenseController::class);
        Route::post('expenses/{expense}/receipt', [\App\Http\Controllers\Api\V1\ExpenseController::class, 'uploadReceipt'])
            ->name('expenses.receipt');
        Route::get('expenses/summary', [\App\Http\Controllers\Api\V1\ExpenseController::class, 'summary'])
            ->name('expenses.summary');

        // Reportes (requiere plan Pro)
        Route::prefix('reports')->name('reports.')->middleware('can:access-reports')->group(function () {
            Route::get('income', [\App\Http\Controllers\Api\V1\ReportController::class, 'income'])
                ->name('income');
            Route::get('expenses', [\App\Http\Controllers\Api\V1\ReportController::class, 'expenses'])
                ->name('expenses');
            Route::get('accounts-receivable', [\App\Http\Controllers\Api\V1\ReportController::class, 'accountsReceivable'])
                ->name('accounts-receivable');
            Route::get('accounts-payable', [\App\Http\Controllers\Api\V1\ReportController::class, 'accountsPayable'])
                ->name('accounts-payable');
            Route::get('asset-utilization', [\App\Http\Controllers\Api\V1\ReportController::class, 'assetUtilization'])
                ->name('asset-utilization');
            Route::get('balance', [\App\Http\Controllers\Api\V1\ReportController::class, 'balance'])
                ->name('balance');
            Route::post('export-pdf', [\App\Http\Controllers\Api\V1\ReportController::class, 'exportPdf'])
                ->name('export-pdf');
        });

        // Configuración
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\SettingController::class, 'index'])
                ->name('index');
            Route::put('/', [\App\Http\Controllers\Api\V1\SettingController::class, 'update'])
                ->name('update');
            Route::post('logo', [\App\Http\Controllers\Api\V1\SettingController::class, 'uploadLogo'])
                ->name('logo');
        });

        // Notificaciones
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index'])
                ->name('index');
            Route::get('unread-count', [\App\Http\Controllers\Api\V1\NotificationController::class, 'unreadCount'])
                ->name('unread-count');
            Route::patch('{notification}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markRead'])
                ->name('read');
            Route::post('read-all', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAllRead'])
                ->name('read-all');
        });

    }); // end auth:sanctum

}); // end v1

