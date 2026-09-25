<?php

use Ispos\Backoffice\Http\Controllers\AppearanceController;
use Ispos\Backoffice\Http\Controllers\Admin\AuditLogController;
use Ispos\Backoffice\Http\Controllers\Admin\ChartOfAccountController;
use Ispos\Backoffice\Http\Controllers\Admin\JournalEntryController;
use Ispos\Backoffice\Http\Controllers\Admin\TrialBalanceReportController;
use Ispos\Backoffice\Http\Controllers\Admin\VendorBillController;
use Ispos\Backoffice\Http\Controllers\Admin\SupplierPaymentController;
use Ispos\Backoffice\Http\Controllers\Admin\ApAgingReportController;
use Ispos\Backoffice\Http\Controllers\Admin\ProfitAndLossReportController;
use Ispos\Backoffice\Http\Controllers\Admin\BalanceSheetReportController;
use Ispos\Backoffice\Http\Controllers\Admin\BrandController;
use Ispos\Backoffice\Http\Controllers\Admin\CategoryController;
use Ispos\Backoffice\Http\Controllers\Admin\CompanyController;
use Ispos\Backoffice\Http\Controllers\Admin\CustomerController;
use Ispos\Backoffice\Http\Controllers\Admin\LoyaltyProgramController;
use Ispos\Backoffice\Http\Controllers\Admin\MembershipPlanController;
use Ispos\Backoffice\Http\Controllers\Admin\OnlineOrderController;
use Ispos\Backoffice\Http\Controllers\Admin\OnlineStoreSettingController;
use Ispos\Backoffice\Http\Controllers\Admin\ProductSalesReportController;
use Ispos\Backoffice\Http\Controllers\Admin\PromotionController;
use Ispos\Backoffice\Http\Controllers\Storefront\StorefrontAuthController;
use Ispos\Backoffice\Http\Controllers\Storefront\StorefrontCartController;
use Ispos\Backoffice\Http\Controllers\Storefront\StorefrontCheckoutController;
use Ispos\Backoffice\Http\Controllers\Storefront\StorefrontController;
use Ispos\Backoffice\Http\Controllers\Storefront\StorefrontFavoriteController;
use Ispos\Backoffice\Http\Controllers\Storefront\StorefrontOrderController;
use Ispos\Backoffice\Http\Controllers\Storefront\StorefrontRatingController;
use Ispos\Backoffice\Http\Controllers\Admin\SalesRegisterReportController;
use Ispos\Backoffice\Http\Controllers\Admin\SalesSummaryReportController;
use Ispos\Backoffice\Http\Controllers\Admin\ShiftReportController;
use Ispos\Backoffice\Http\Controllers\Admin\InventoryAdjustmentController;
use Ispos\Backoffice\Http\Controllers\Admin\LowStockAlertController;
use Ispos\Backoffice\Http\Controllers\Admin\StockMovementController;
use Ispos\Backoffice\Http\Controllers\Admin\StockTransferController;
use Ispos\Backoffice\Http\Controllers\Admin\StoreInventoryController;
use Ispos\Backoffice\Http\Controllers\Admin\PriceGroupController;
use Ispos\Backoffice\Http\Controllers\Admin\ProductController;
use Ispos\Backoffice\Http\Controllers\Admin\PurchaseOrderController;
use Ispos\Backoffice\Http\Controllers\Admin\PurchaseReceivingController;
use Ispos\Backoffice\Http\Controllers\Admin\PurchaseReturnController;
use Ispos\Backoffice\Http\Controllers\Admin\SupplierController;
use Ispos\Backoffice\Http\Controllers\Admin\PosDeviceController;
use Ispos\Backoffice\Http\Controllers\Admin\RegisterController;
use Ispos\Backoffice\Http\Controllers\Admin\SalesPlanController;
use Ispos\Backoffice\Http\Controllers\Admin\TaxController;
use Ispos\Backoffice\Http\Controllers\Admin\UnitController;
use Ispos\Backoffice\Http\Controllers\Admin\RoleController;
use Ispos\Backoffice\Http\Controllers\Admin\PosUiSettingsController;
use Ispos\Backoffice\Http\Controllers\Admin\SettingController;
use Ispos\Backoffice\Http\Controllers\Admin\StoreController;
use Ispos\Backoffice\Http\Controllers\Admin\RoleMemberController;
use Ispos\Backoffice\Http\Controllers\Admin\StoreEmployeeController;
use Ispos\Backoffice\Http\Controllers\Admin\UserController;
use Ispos\Backoffice\Http\Controllers\Auth\LoginContextController;
use Ispos\Backoffice\Http\Controllers\DashboardController;
use Ispos\Backoffice\Http\Controllers\Kds\KdsSessionController;
use Ispos\Backoffice\Http\Controllers\Kds\KdsTerminalController;
use Ispos\Backoffice\Http\Controllers\Kds\KdsTicketController;
use Ispos\Backoffice\Http\Controllers\LocaleController;
use Ispos\Backoffice\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::prefix('store')->name('storefront.')->group(function () {
    Route::get('{slug}', [StorefrontController::class, 'show'])->name('show');
    Route::get('{slug}/cart', [StorefrontCartController::class, 'show'])->name('cart');
    Route::get('{slug}/login', [StorefrontAuthController::class, 'showLogin'])->name('login');
    Route::post('{slug}/login', [StorefrontAuthController::class, 'login'])->name('login.store');
    Route::get('{slug}/register', [StorefrontAuthController::class, 'showRegister'])->name('register');
    Route::post('{slug}/register', [StorefrontAuthController::class, 'register'])->name('register.store');
    Route::post('{slug}/logout', [StorefrontAuthController::class, 'logout'])->name('logout');

    Route::middleware('storefront.customer')->group(function () {
        Route::post('{slug}/products/{product}/favorite', [StorefrontFavoriteController::class, 'toggle'])->name('favorites.toggle');
        Route::post('{slug}/products/{product}/rate', [StorefrontRatingController::class, 'store'])->name('ratings.store');
        Route::get('{slug}/checkout', [StorefrontCheckoutController::class, 'create'])->name('checkout');
        Route::post('{slug}/checkout', [StorefrontCheckoutController::class, 'store'])->name('checkout.store');
        Route::get('{slug}/orders', [StorefrontOrderController::class, 'index'])->name('orders.index');
    });

    Route::get('{slug}/orders/{uuid}', [StorefrontOrderController::class, 'show'])->name('orders.show');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/login-context', [LoginContextController::class, 'create'])->name('login-context.create');
    Route::post('/login-context', [LoginContextController::class, 'store'])->name('login-context.store');

    Route::middleware('backoffice.context')->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::middleware('permission:kds.view')
        ->prefix('kds')
        ->name('kds.')
        ->group(function () {
            Route::get('/', [KdsTerminalController::class, 'index'])->name('index');
            Route::post('/session', [KdsSessionController::class, 'store'])->name('session.store');
            Route::get('/tickets', [KdsTicketController::class, 'index'])->name('tickets.index');
            Route::patch('/tickets/{kitchen_ticket}', [KdsTicketController::class, 'update'])->name('tickets.update');
        });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('backoffice.company')->group(function () {
            Route::resource('companies', CompanyController::class)->except(['show']);
            Route::resource('roles', RoleController::class)->except(['show']);
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/ispos', [SettingController::class, 'updateIsposBranding'])->name('settings.ispos.update');
            Route::put('settings/translation', [SettingController::class, 'updateTranslation'])->name('settings.translation.update');
            Route::get('pos-ui', [PosUiSettingsController::class, 'edit'])->name('pos-ui.edit');
            Route::put('pos-ui', [PosUiSettingsController::class, 'update'])->name('pos-ui.update');
            Route::middleware('permission:sync.manage')->group(function () {
                Route::get('pos-devices', [PosDeviceController::class, 'index'])->name('pos-devices.index');
                Route::post('pos-devices/{pos_device}/revoke', [PosDeviceController::class, 'revoke'])->name('pos-devices.revoke');
            });
        });

        Route::resource('stores', StoreController::class)->except(['show']);
        Route::resource('sales-plans', SalesPlanController::class)->except(['show']);
        Route::resource('registers', RegisterController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('brands', BrandController::class)->except(['show']);
        Route::resource('units', UnitController::class)->except(['show']);
        Route::resource('taxes', TaxController::class)->except(['show']);
        Route::resource('price-groups', PriceGroupController::class)->except(['show']);
        Route::get('products/bulk-create', [ProductController::class, 'bulkCreate'])->name('products.bulk-create');
        Route::post('products/bulk-create', [ProductController::class, 'bulkStore'])->name('products.bulk-store');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::middleware('permission:inventory.view')->group(function () {
            Route::get('inventory/stock', [StoreInventoryController::class, 'index'])->name('inventory.stock.index');
            Route::get('inventory/movements', [StockMovementController::class, 'index'])->name('inventory.movements.index');
            Route::get('inventory/low-stock', [LowStockAlertController::class, 'index'])->name('inventory.low-stock');
            Route::get('inventory/transfers', [StockTransferController::class, 'index'])->name('inventory.transfers.index');
        });
        Route::middleware('permission:inventory.adjust')->group(function () {
            Route::get('inventory/adjustments/create', [InventoryAdjustmentController::class, 'create'])->name('inventory.adjustments.create');
            Route::post('inventory/adjustments', [InventoryAdjustmentController::class, 'store'])->name('inventory.adjustments.store');
            Route::get('inventory/transfers/create', [StockTransferController::class, 'create'])->name('inventory.transfers.create');
            Route::post('inventory/transfers', [StockTransferController::class, 'store'])->name('inventory.transfers.store');
        });
        Route::middleware('permission:inventory.view')->group(function () {
            Route::get('inventory/transfers/{transfer}', [StockTransferController::class, 'show'])->name('inventory.transfers.show');
        });
        Route::middleware('permission:customers.view')->group(function () {
            Route::resource('customers', CustomerController::class)->except(['show']);
            Route::post('customers/{customer}/loyalty-adjust', [CustomerController::class, 'adjustLoyalty'])->name('customers.loyalty-adjust');
            Route::post('customers/{customer}/membership', [CustomerController::class, 'assignMembership'])->name('customers.membership.assign');
        });
        Route::middleware('permission:loyalty.view')->group(function () {
            Route::resource('loyalty-programs', LoyaltyProgramController::class)->except(['show']);
        });
        Route::middleware('permission:memberships.view')->group(function () {
            Route::resource('membership-plans', MembershipPlanController::class)->except(['show']);
        });
        Route::middleware('permission:promotions.view')->group(function () {
            Route::resource('promotions', PromotionController::class)->except(['show']);
            Route::post('promotions/{promotion}/activate', [PromotionController::class, 'activate'])->name('promotions.activate');
            Route::post('promotions/{promotion}/cancel', [PromotionController::class, 'cancel'])->name('promotions.cancel');
        });
        Route::middleware('permission:online_store.view')->group(function () {
            Route::resource('online-store-settings', OnlineStoreSettingController::class)->except(['show']);
            Route::post('online-store-settings/{online_store_setting}/publish', [OnlineStoreSettingController::class, 'publish'])
                ->name('online-store-settings.publish');
            Route::resource('online-orders', OnlineOrderController::class)->only(['index', 'show']);
            Route::post('online-orders/{online_order}/accept', [OnlineOrderController::class, 'accept'])->name('online-orders.accept');
            Route::post('online-orders/{online_order}/reject', [OnlineOrderController::class, 'reject'])->name('online-orders.reject');
            Route::post('online-orders/{online_order}/ready', [OnlineOrderController::class, 'markReady'])->name('online-orders.ready');
            Route::post('online-orders/{online_order}/complete', [OnlineOrderController::class, 'complete'])->name('online-orders.complete');
            Route::post('online-orders/{online_order}/cancel', [OnlineOrderController::class, 'cancel'])->name('online-orders.cancel');
        });
        Route::middleware('permission:reports.view')->prefix('reports')->name('reports.')->group(function () {
            Route::get('sales-summary', [SalesSummaryReportController::class, 'index'])->name('sales-summary.index');
            Route::get('sales-register', [SalesRegisterReportController::class, 'index'])->name('sales-register.index');
            Route::get('product-sales', [ProductSalesReportController::class, 'index'])->name('product-sales.index');
            Route::get('shift-reports', [ShiftReportController::class, 'index'])->name('shift-reports.index');
            Route::get('shift-reports/{pos_shift}', [ShiftReportController::class, 'show'])->name('shift-reports.show');
        });
        Route::middleware('permission:reports.export')->group(function () {
            Route::get('reports/sales-register/export', [SalesRegisterReportController::class, 'export'])->name('reports.sales-register.export');
        });
        Route::middleware('permission:accounting.view')->group(function () {
            Route::resource('chart-of-accounts', ChartOfAccountController::class)->except(['show']);
            Route::resource('journal-entries', JournalEntryController::class)->except(['show']);
            Route::get('accounting/trial-balance', [TrialBalanceReportController::class, 'index'])->name('accounting.trial-balance.index');
            Route::get('accounting/profit-and-loss', [ProfitAndLossReportController::class, 'index'])->name('accounting.profit-and-loss.index');
            Route::get('accounting/balance-sheet', [BalanceSheetReportController::class, 'index'])->name('accounting.balance-sheet.index');
        });
        Route::middleware('permission:accounting.post')->group(function () {
            Route::post('journal-entries/{journal_entry}/post', [JournalEntryController::class, 'post'])->name('journal-entries.post');
        });
        Route::middleware('permission:ap.view')->group(function () {
            Route::get('vendor-bills', [VendorBillController::class, 'index'])->name('vendor-bills.index');
            Route::get('supplier-payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');
            Route::get('accounting/ap-aging', [ApAgingReportController::class, 'index'])->name('accounting.ap-aging.index');
        });
        Route::middleware('permission:ap.pay')->group(function () {
            Route::get('supplier-payments/create', [SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
            Route::post('supplier-payments', [SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
        });
        Route::middleware('permission:purchasing.view')->group(function () {
            Route::resource('suppliers', SupplierController::class)->except(['show']);
            Route::resource('purchase-orders', PurchaseOrderController::class)->except(['show']);
            Route::get('purchase-orders/{purchase_order}/receive', [PurchaseReceivingController::class, 'create'])->name('purchase-orders.receive.create');
            Route::post('purchase-orders/{purchase_order}/receive', [PurchaseReceivingController::class, 'store'])->name('purchase-orders.receive.store');
            Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
            Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
            Route::resource('purchase-returns', PurchaseReturnController::class)->except(['show']);
            Route::post('purchase-returns/{purchase_return}/post', [PurchaseReturnController::class, 'post'])->name('purchase-returns.post');
        });
        Route::resource('users', UserController::class)->except(['show']);
        Route::prefix('users/store-employees')->name('users.store-employees.')->group(function () {
            Route::get('/', [StoreEmployeeController::class, 'index'])->name('index');
            Route::post('/bulk', [StoreEmployeeController::class, 'bulkStore'])->name('bulk-store');
            Route::delete('/{user}/{store}', [StoreEmployeeController::class, 'destroy'])->name('destroy');
        });
        Route::prefix('users/role-members')->name('users.role-members.')->group(function () {
            Route::get('/', [RoleMemberController::class, 'index'])->name('index');
            Route::post('/bulk', [RoleMemberController::class, 'bulkStore'])->name('bulk-store');
            Route::delete('/{user}/{role}', [RoleMemberController::class, 'destroy'])->name('destroy');
        });
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::get('/appearance', [AppearanceController::class, 'edit'])->name('appearance.edit');
    Route::put('/appearance', [AppearanceController::class, 'update'])->name('appearance.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
