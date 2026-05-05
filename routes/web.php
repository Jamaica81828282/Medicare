<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Cashier\DashboardController as CashierDashboard;
use App\Http\Controllers\Customer\KioskController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\QueueController;
use App\Models\User;
 
Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', function () {
    /** @var User|null $user */
    $user = Auth::user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasRole('cashier')) {
        return redirect()->route('cashier.dashboard');
    }

    if ($user->hasRole('customer')) {
        return redirect()->route('kiosk.index');
    }

    Auth::logout();

    return redirect()->route('login')
        ->with('error', 'Your account has no role assigned. Please contact the administrator.');
})->middleware('auth')->name('dashboard');

// ── PUBLIC (no auth) — for the TV display screen ───────────────────
Route::get('/queue/display',      [QueueController::class, 'display'])->name('queue.display');
Route::get('/queue/display/poll', [QueueController::class, 'poll'])->name('queue.display.poll');

Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/cashier/{id}/view', [AdminController::class, 'viewCashierDashboard'])->name('admin.cashier.view');

    // Products
    Route::get('/products',             [AdminController::class, 'getProducts'])->name('admin.products.list');
    Route::get('/products/{id}',        [AdminController::class, 'getProduct'])->name('admin.products.show');   // NEW
    Route::post('/products',            [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::post('/products/{id}',       [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::patch('/products/{id}/toggle',[AdminController::class, 'toggleProduct'])->name('admin.products.toggle');
    Route::get('/products/{id}/image', [AdminController::class, 'serveProductImage'])->name('admin.products.image');
    // Batches
    Route::get('/batches',                       [AdminController::class, 'getBatches'])->name('admin.batches.list');
    Route::post('/batches',                      [AdminController::class, 'storeBatch'])->name('admin.batches.store');
    Route::patch('/batches/{id}/confirm-stock',  [AdminController::class, 'confirmBatchStock'])->name('admin.batches.confirm');
    Route::delete('/batches/{id}',               [AdminController::class, 'deleteBatch'])->name('admin.batches.delete');

    // Suppliers
    Route::get('/suppliers',       [AdminController::class, 'getSuppliers'])->name('admin.suppliers.list');
    Route::post('/suppliers',      [AdminController::class, 'storeSupplier'])->name('admin.suppliers.store');
    Route::put('/suppliers/{id}',  [AdminController::class, 'updateSupplier'])->name('admin.suppliers.update');

    // Invoices — export MUST be before {id} routes
    Route::get('/invoices',                [AdminController::class, 'getInvoices'])->name('admin.invoices.list');
    Route::get('/invoices/export',         [AdminController::class, 'exportInvoices'])->name('admin.invoices.export');
    Route::get('/invoices/{id}/items',     [AdminController::class, 'getInvoiceItems'])->name('admin.invoices.items');
    Route::post('/invoices/{id}/void',     [AdminController::class, 'adminVoidInvoice'])->name('admin.invoices.void');

    // Users
    Route::get('/users',       [AdminController::class, 'getUsers'])->name('admin.users.list');
    Route::post('/users',      [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/users/{id}',  [AdminController::class, 'updateUser'])->name('admin.users.update');
});

// Cashier Routes
Route::prefix('cashier')->name('cashier.')->middleware(['auth', 'role:cashier|admin'])->group(function () {
    Route::get('/dashboard',          [CashierDashboard::class, 'index'])           ->name('dashboard');
    Route::get('/invoice/{id}',       [CashierDashboard::class, 'getInvoice'])      ->name('invoice.get');
    Route::post('/payment',           [CashierDashboard::class, 'processPayment'])  ->name('payment.process');
    Route::post('/void',              [CashierDashboard::class, 'voidInvoice'])     ->name('invoice.void');
    Route::get('/invoice/{id}/print', [CashierDashboard::class, 'printInvoice'])   ->name('invoice.print');
    Route::get('/invoices/search',    [CashierDashboard::class, 'searchInvoices']) ->name('invoice.search');  // ← fixed
    Route::get('/products/lookup',    [CashierDashboard::class, 'productLookup'])  ->name('product.lookup');
    Route::get( '/queue',       [QueueController::class, 'cashierQueue'])->name('queue.list');
    Route::post('/queue/call',  [QueueController::class, 'call'])->name('queue.call');
    Route::post('/queue/done',  [QueueController::class, 'done'])->name('queue.done');
    Route::post('/queue/skip',  [QueueController::class, 'skip'])->name('queue.skip');
    Route::post('/queue/reset', [QueueController::class, 'reset'])->name('queue.reset');
    });

// Kiosk Routes
Route::prefix('kiosk')->name('kiosk.')->group(function () {
    Route::get('/',                   [KioskController::class, 'index'])->name('index');
    Route::get('/search-customer',    [KioskController::class, 'searchCustomer'])->name('search');
    Route::post('/submit-order',      [KioskController::class, 'submitOrder'])->name('submit');
    Route::post('/update',            [KioskController::class, 'update'])->name('update');
    Route::post('/upload-image/{id}', [KioskController::class, 'uploadImage'])->name('image');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

require __DIR__.'/auth.php';