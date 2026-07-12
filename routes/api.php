<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\OrganizationSessionController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Middleware\EnsureOrganizationAccess;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {

        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
        });
    });

    Route::get('/debug-extensions', function () {
        return response()->json([
            'pdo_drivers' => \PDO::getAvailableDrivers(),
            'pgsql_loaded' => extension_loaded('pgsql'),
            'pdo_pgsql_loaded' => extension_loaded('pdo_pgsql'),
            'php_version' => phpversion(),
            'loaded_extensions' => get_loaded_extensions(),
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'role:super_admin,admin'])
        ->prefix('users')
        ->group(function () {

            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
            Route::put('/{id}/toggle-active', [UserController::class, 'toggleActive']);
        });

    /*
    |--------------------------------------------------------------------------
    | Organizations
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')
        ->prefix('organizations')
        ->group(function () {

            Route::get('/', [OrganizationController::class, 'index']);
            Route::get('/me', [OrganizationSessionController::class, 'myOrganizations']);

            Route::middleware('role:super_admin,admin')->group(function () {

                Route::post('/', [OrganizationController::class, 'store'])
                    ->middleware('role:super_admin');

                Route::get('/{organization}', [OrganizationController::class, 'show']);
                Route::put('/{organization}', [OrganizationController::class, 'update']);
                Route::delete('/{organization}', [OrganizationController::class, 'destroy']);

                Route::get('/{organization}/members', [OrganizationController::class, 'members']);
                Route::post('/{organization}/members', [OrganizationController::class, 'addMember']);
                Route::post('/{organization}/members/create', [UserController::class, 'storeMember']);
                Route::delete('/{organization}/members/{user_id}', [OrganizationController::class, 'removeMember']);
            });
        });

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'role:admin',
        EnsureOrganizationAccess::class
    ])->prefix('categories')->group(function () {

        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'role:admin,vendeur',
        EnsureOrganizationAccess::class
    ])->prefix('products')->group(function () {

        Route::get('/low-stock', [ProductController::class, 'lowStock']);
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        Route::put('/{id}/toggle-active', [ProductController::class, 'toggleActive']);
    });

    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'role:admin,vendeur',
        EnsureOrganizationAccess::class
    ])->prefix('stock')->group(function () {

        Route::get('/movements', [StockController::class, 'movements']);
        Route::get('/movements/{product_id}', [StockController::class, 'productMovements']);
        Route::post('/entry', [StockController::class, 'entry']);
        Route::post('/adjustment', [StockController::class, 'adjustment']);
        Route::post('/loss', [StockController::class, 'loss']);
    });

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'role:admin,vendeur',
        EnsureOrganizationAccess::class
    ])->prefix('clients')->group(function () {

        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
        Route::get('/{id}/sales', [ClientController::class, 'sales']);
    });

    /*
    |--------------------------------------------------------------------------
    | Sales
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'role:admin,vendeur',
        EnsureOrganizationAccess::class
    ])->prefix('sales')->group(function () {

        Route::get('/', [SaleController::class, 'index']);
        Route::post('/', [SaleController::class, 'store']);
        Route::get('/{id}', [SaleController::class, 'show']);
        Route::put('/{id}', [SaleController::class, 'update']);
        Route::delete('/{id}', [SaleController::class, 'destroy']);
        Route::put('/{id}/status', [SaleController::class, 'updateStatus']);
        Route::post('/{id}/return', [SaleController::class, 'returnSale']);
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        EnsureOrganizationAccess::class
    ])->prefix('dashboard')->group(function () {

        Route::get('/kpis', [DashboardController::class, 'kpis']);
        Route::get('/charts/sales', [DashboardController::class, 'chartsSales']);
        Route::get('/charts/categories', [DashboardController::class, 'chartsCategories']);
        Route::get('/alerts', [DashboardController::class, 'alerts']);
    });

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::middleware([
        'auth:sanctum',
        'role:admin',
        EnsureOrganizationAccess::class
    ])->prefix('reports')->group(function () {

        Route::get('/stock', [ReportController::class, 'stock']);
        Route::get('/sales', [ReportController::class, 'sales']);
        Route::get('/movements', [ReportController::class, 'movements']);
        Route::post('/export', [ReportController::class, 'export']);
        Route::post('/sales/{id}/export', [ReportController::class, 'exportSingleSale']);
    });

});



// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Api\AuthController;
// use App\Http\Controllers\Api\UserController;
// use App\Http\Controllers\Api\OrganizationController;
// use App\Http\Controllers\Api\OrganizationSessionController;
// use App\Http\Controllers\Api\CategoryController;
// use App\Http\Controllers\Api\ProductController;
// use App\Http\Controllers\Api\StockController;
// use App\Http\Controllers\Api\ClientController;
// use App\Http\Controllers\Api\SaleController;
// use App\Http\Controllers\Api\DashboardController;
// use App\Http\Controllers\Api\ReportController;

// Route::prefix('v1')->group(function () {

//     // ─── Auth (public) ────────────────────────────────────────
//     Route::prefix('auth')->group(function () {
//         Route::post('login', [AuthController::class, 'login']);

//         Route::middleware('auth:sanctum')->group(function () {
//             Route::post('logout', [AuthController::class, 'logout']);
//             Route::get('me',      [AuthController::class, 'me']);
//             Route::put('profile', [AuthController::class, 'updateProfile']);
//         });
//     });

//     // ─── Users (super_admin + admin) ──────────────────────────
//     Route::middleware(['auth:sanctum', 'role:super_admin,admin'])
//         ->prefix('users')
//         ->group(function () {
//             Route::get('/',                       [UserController::class, 'index']);
//             Route::post('/',                      [UserController::class, 'store']);
//             Route::get('/{id}',                   [UserController::class, 'show']);
//             Route::put('/{id}',                   [UserController::class, 'update']);
//             Route::delete('/{id}',                [UserController::class, 'destroy']);
//             Route::put('/{id}/toggle-active',     [UserController::class, 'toggleActive']);
//         });

//     // ─── Organisations ────────────────────────────────────────
//     Route::middleware(['auth:sanctum'])->prefix('organizations')->group(function () {
//         // Accessible à tous les rôles authentifiés (vendeur inclus)
//         Route::get('/', [OrganizationController::class, 'index']);
//         Route::get('/me', [OrganizationSessionController::class, 'myOrganizations']);

//         // Gestion administrative (super_admin + admin seulement)
//         Route::middleware(['role:super_admin,admin'])->group(function () {
//             Route::post('/',   [OrganizationController::class, 'store'])
//                 ->middleware('role:super_admin');

//             Route::get('/{organization}',    [OrganizationController::class, 'show']);
//             Route::put('/{organization}',    [OrganizationController::class, 'update']);
//             Route::delete('/{organization}', [OrganizationController::class, 'destroy']);

//             Route::get('/{organization}/members',              [OrganizationController::class, 'members']);
//             Route::post('/{organization}/members',             [OrganizationController::class, 'addMember']);
//             Route::post('/{organization}/members/create',      [UserController::class, 'storeMember']);
//             Route::delete('/{organization}/members/{user_id}', [OrganizationController::class, 'removeMember']);
//         });
//     });


//     // ─── Categories (admin) ───────────────────────────────────
//     Route::middleware(['auth:sanctum', 'role:admin']) ->prefix('categories')->group(function () {
//             Route::get('/',        [CategoryController::class, 'index']);
//             Route::post('/',       [CategoryController::class, 'store']);
//             Route::get('/{id}',    [CategoryController::class, 'show']);
//             Route::put('/{id}',    [CategoryController::class, 'update']);
//             Route::delete('/{id}', [CategoryController::class, 'destroy']);
//         });

//     // ─── Products (admin + vendeur) ───────────────────────────
//     Route::middleware(['auth:sanctum', 'role:admin,vendeur'])
//         ->prefix('products')
//         ->group(function () {
//             Route::get('/low-stock',          [ProductController::class, 'lowStock']);
//             Route::get('/',                   [ProductController::class, 'index']);
//             Route::post('/',                  [ProductController::class, 'store']);
//             Route::get('/{id}',               [ProductController::class, 'show']);
//             Route::put('/{id}',               [ProductController::class, 'update']);
//             Route::delete('/{id}',            [ProductController::class, 'destroy']);
//             Route::put('/{id}/toggle-active', [ProductController::class, 'toggleActive']);
//         });

//     // ─── Stock (admin + vendeur) ──────────────────────────────
//     Route::middleware(['auth:sanctum', 'role:admin,vendeur'])
//         ->prefix('stock')
//         ->group(function () {
//             Route::get('/movements',               [StockController::class, 'movements']);
//             Route::get('/movements/{product_id}',  [StockController::class, 'productMovements']);
//             Route::post('/entry',                  [StockController::class, 'entry']);
//             Route::post('/adjustment',             [StockController::class, 'adjustment']);
//             Route::post('/loss',                   [StockController::class, 'loss']);
//         });

//     // ─── Clients (admin + vendeur) ────────────────────────────
//     Route::middleware(['auth:sanctum', 'role:admin,vendeur'])
//         ->prefix('clients')
//         ->group(function () {
//             Route::get('/',           [ClientController::class, 'index']);
//             Route::post('/',          [ClientController::class, 'store']);
//             Route::get('/{id}',       [ClientController::class, 'show']);
//             Route::put('/{id}',       [ClientController::class, 'update']);
//             Route::delete('/{id}',    [ClientController::class, 'destroy']);
//             Route::get('/{id}/sales', [ClientController::class, 'sales']);
//         });

//     // ─── Ventes (admin + vendeur) ─────────────────────────────
//     Route::middleware(['auth:sanctum', 'role:admin,vendeur'])
//         ->prefix('sales')
//         ->group(function () {
//             Route::get('/',             [SaleController::class, 'index']);
//             Route::post('/',            [SaleController::class, 'store']);
//             Route::get('/{id}',         [SaleController::class, 'show']);
//             Route::put('/{id}',         [SaleController::class, 'update']);
//             Route::delete('/{id}',      [SaleController::class, 'destroy']);
//             Route::put('/{id}/status',  [SaleController::class, 'updateStatus']);
//             Route::post('/{id}/return', [SaleController::class, 'return']);
//         });

//     // ─── Dashboard (tous les authentifiés) ────────────────────
//     Route::middleware(['auth:sanctum'])
//         ->prefix('dashboard')
//         ->group(function () {
//             Route::get('/kpis',              [DashboardController::class, 'kpis']);
//             Route::get('/charts/sales',      [DashboardController::class, 'chartsSales']);
//             Route::get('/charts/categories', [DashboardController::class, 'chartsCategories']);
//             Route::get('/alerts',            [DashboardController::class, 'alerts']);
//         });

//     // ─── Rapports (admin) ─────────────────────────────────────
//     Route::middleware(['auth:sanctum', 'role:admin'])
//         ->prefix('reports')
//         ->group(function () {
//             Route::get('/stock',     [ReportController::class, 'stock']);
//             Route::get('/sales',     [ReportController::class, 'sales']);
//             Route::get('/movements', [ReportController::class, 'movements']);
//             Route::post('/export',   [ReportController::class, 'export']);

//             // Détail vente (CSV/PDF)
//             Route::post('/sales/{id}/export', [ReportController::class, 'exportSingleSale']);
//         });
// });

