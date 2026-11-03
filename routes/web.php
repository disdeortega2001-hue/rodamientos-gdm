<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Panel\AuthController;
use App\Http\Controllers\Panel\CategoryController;
use App\Http\Controllers\Panel\CustomerController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\DataController;
use App\Http\Controllers\Panel\InventoryController;
use App\Http\Controllers\Panel\OrderController;
use App\Http\Controllers\Panel\PaymentController;
use App\Http\Controllers\Panel\ProductController;
use App\Http\Controllers\Panel\QuoteController;
use App\Http\Controllers\Panel\ReportController;
use App\Http\Controllers\Panel\SettingController;
use App\Http\Controllers\Panel\UserController;
use App\Http\Controllers\PublicQuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home']);
Route::get('/empresa', [PageController::class, 'company']);
Route::get('/creditos', [PageController::class, 'credits']);
Route::get('/guia', [PageController::class, 'guide']);
Route::get('/media/{path}', [MediaController::class, 'show'])->where('path', '.*');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalogo/{product}', [CatalogController::class, 'show'])->name('catalog.show');
Route::view('/carrito', 'cart')->name('cart');
Route::post('/carrito/resumen', [CheckoutController::class, 'summary']);
Route::post('/pedido', [CheckoutController::class, 'store'])->middleware('throttle:checkout');
Route::get('/cotizacion/{token}', [PublicQuoteController::class, 'show']);
Route::get('/cotizacion/{token}/pdf', [PublicQuoteController::class, 'pdf']);

Route::prefix('panel')->group(function () {
    Route::get('acceso', [AuthController::class, 'show'])->name('login');
    Route::post('acceso', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth', 'active', 'decimal'])->group(function () {
        Route::post('salir', [AuthController::class, 'logout']);
        Route::get('/', DashboardController::class);

        Route::get('productos', [ProductController::class, 'index']);
        Route::get('inventario', [InventoryController::class, 'index']);
        Route::get('inventario/{product:id}', [InventoryController::class, 'show']);

        Route::get('pedidos', [OrderController::class, 'index']);
        Route::get('pedidos/nuevo', [OrderController::class, 'create']);
        Route::post('pedidos', [OrderController::class, 'store'])->middleware('once');
        Route::get('pedidos/{order}', [OrderController::class, 'show']);
        Route::post('pedidos/{order}/confirmar', [OrderController::class, 'confirm']);
        Route::post('pedidos/{order}/entregar', [OrderController::class, 'deliver']);
        Route::post('pedidos/{order}/cancelar', [OrderController::class, 'cancel']);
        Route::put('pedidos/{order}/lineas/{item}', [OrderController::class, 'updateLine']);
        Route::post('pedidos/{order}/pagos', [PaymentController::class, 'store'])->middleware('once');

        Route::get('cotizaciones', [QuoteController::class, 'index']);
        Route::get('cotizaciones/nueva', [QuoteController::class, 'create']);
        Route::post('cotizaciones', [QuoteController::class, 'store'])->middleware('once');
        Route::get('cotizaciones/{quote}', [QuoteController::class, 'show']);
        Route::post('cotizaciones/{quote}/convertir', [QuoteController::class, 'convert']);

        Route::get('clientes', [CustomerController::class, 'index']);
        Route::get('clientes/{customer}', [CustomerController::class, 'show']);
        Route::put('clientes/{customer}', [CustomerController::class, 'update']);

        Route::middleware('can:manage-all')->group(function () {
            Route::get('productos/nuevo', [ProductController::class, 'create']);
            Route::post('productos', [ProductController::class, 'store']);
            Route::get('productos/{product:id}/editar', [ProductController::class, 'edit']);
            Route::put('productos/{product:id}', [ProductController::class, 'update']);
            Route::delete('productos/{product:id}', [ProductController::class, 'destroy']);
            Route::post('productos/{product:id}/imagenes', [ProductController::class, 'storeImage']);
            Route::delete('imagenes/{image}', [ProductController::class, 'destroyImage']);

            Route::get('categorias', [CategoryController::class, 'index']);
            Route::post('categorias', [CategoryController::class, 'store']);
            Route::put('categorias/{category}', [CategoryController::class, 'update']);
            Route::delete('categorias/{category}', [CategoryController::class, 'destroy']);

            Route::post('inventario/{product:id}/entradas', [InventoryController::class, 'entry']);
            Route::post('inventario/{product:id}/ajustes', [InventoryController::class, 'adjust']);

            Route::delete('pagos/{payment}', [PaymentController::class, 'destroy']);

            Route::get('reportes', [ReportController::class, 'index']);
            Route::get('reportes/exportar', [ReportController::class, 'export']);

            Route::get('usuarios', [UserController::class, 'index']);
            Route::post('usuarios', [UserController::class, 'store']);
            Route::put('usuarios/{user}', [UserController::class, 'update']);

            Route::get('importar', [DataController::class, 'importForm']);
            Route::post('importar', [DataController::class, 'import']);
            Route::get('respaldo', [DataController::class, 'backup']);

            Route::get('ajustes', [SettingController::class, 'edit']);
            Route::put('ajustes', [SettingController::class, 'update']);
        });
    });
});
