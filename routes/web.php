<?php

use App\Helpers\SeasonHelper;
use App\Helpers\SettingsHelper;
use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\AdminHarvestImportController;
use App\Http\Controllers\AdminImportUploadController;
use App\Http\Controllers\AdminMemberController;
use App\Http\Controllers\AdminMemberImportController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminPlantingController;
use App\Http\Controllers\AdminStoreController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminWeatherLogController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CropsDirectoryController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FarmerCropController;
use App\Http\Controllers\FarmerDashboardController;
use App\Http\Controllers\FarmerNotificationController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\HarvestReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PasswordSetupController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\StoreDirectoryController;
use App\Http\Controllers\StoreOwnerActivityController;
use App\Http\Controllers\WeatherController;
use App\Models\DecisionFormula;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Guest Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard.redirect', ['role' => Auth::user()->role]);
    }

    // The landing page IS the sign-in page: render it at the root URL so the
    // canonical, sitemap-listed URL (https://aquilala.me/) returns real
    // indexable content instead of a redirect hop to /login. /login still
    // works for bookmarks and now canonicalises back to the root.
    return view('auth.login');
})->name('home');

// Guest-only access. Completing activation and password reset sign the
// user in / back out respectively, so every step of these flows stays
// guest-only.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:login');

    // ── Account activation (admin-created farmer / store-owner / admin accounts) ──
    Route::get('/activate', [AccountActivationController::class, 'index'])->name('account.activation.index');
    Route::post('/activate/verify', [AccountActivationController::class, 'verify'])
        ->name('account.activation.verify')->middleware('throttle:otp');
    Route::post('/activate/verify/resend', [AccountActivationController::class, 'resend'])
        ->name('account.activation.resend')->middleware('throttle:otp');
    Route::get('/activate/password', [AccountActivationController::class, 'password'])
        ->name('account.activation.password');
    Route::post('/activate/password', [AccountActivationController::class, 'complete'])
        ->name('account.activation.complete')->middleware('throttle:otp');

    // ── Forgot password (email OTP; account existence is never disclosed) ──
    Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendRequest'])
        ->name('password.email')->middleware('throttle:password');
    Route::get('/forgot-password/verify', [PasswordResetController::class, 'showVerify'])->name('password.verify');
    Route::post('/forgot-password/verify', [PasswordResetController::class, 'verifyOtp'])
        ->name('password.verify.post')->middleware('throttle:otp');
    Route::get('/forgot-password/reset', [PasswordResetController::class, 'showReset'])->name('password.reset.show');
    Route::post('/forgot-password/reset', [PasswordResetController::class, 'reset'])
        ->name('password.reset')->middleware('throttle:password');

    // ── Set Password (admin-created account invitation link) ──
    // Reached from the emailed "Set Your Password" link. The token is a
    // standard password-reset token; the email query param selects the owner.
    Route::get('/set-password/{token}', [PasswordSetupController::class, 'showSetPassword'])
        ->name('password.setup');
    Route::post('/set-password/{token}', [PasswordSetupController::class, 'storeSetPassword'])
        ->name('password.setup.post')->middleware('throttle:password');

    // Initial-administrator email/OTP verification. Guest-only by design:
    // a pending admin cannot sign in until their email is verified.
    Route::get('/verify-email', [EmailVerificationController::class, 'show'])
        ->name('email.verify.show');
    Route::post('/verify-email', [EmailVerificationController::class, 'verify'])
        ->name('email.verify')->middleware('throttle:otp');
    Route::post('/verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->name('email.verify.resend')->middleware('throttle:otp');
});

// Background weather feed for the sidebar climate widget. Authenticated (and
// throttled) so only signed-in sessions can spend the OpenWeather quota; the
// widget itself only calls this when the server had no cached reading.
Route::middleware('auth')->group(function () {
    Route::get('/weather/current', [WeatherController::class, 'current'])
        ->name('weather.current')
        ->middleware('throttle:30,1');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Requires Session & Active CSRF Token)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'password.setup'])->group(function () {

    // Handled by AuthController@logout to invalidate sessions and prevent 419 Page Expired errors
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Forced first-sign-in password change for admin-created accounts that
    // signed in with the emailed temporary password. Exempt from the
    // password.setup redirect (handled inside EnsurePasswordChanged).
    Route::get('/change-password', [PasswordSetupController::class, 'showChangePassword'])
        ->name('password.change');
    Route::post('/change-password', [PasswordSetupController::class, 'storeChangePassword'])
        ->name('password.change.post')->middleware('throttle:password');

    // Own profile page — available to all roles
    Route::get('/profile', function () {
        return view('profile.index', ['me' => auth()->user()]);
    })->name('profile.index');

    // The member's own picture: upload it, replace it, or remove it and
    // go back to the initials. It follows the account on every device.
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.photo.remove');

    // The member's own address. The one part of the profile they keep
    // current themselves; the rest of the record is the admin's.
    Route::patch('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.address.update');

    // Personal UI preferences (side menu theme) — available to all roles
    Route::post('/settings/ui', function (Request $request) {
        $theme = (string) $request->input('theme');

        if (! in_array($theme, array_keys(config('sidebar.themes')), true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid theme.'], 422);
        }

        // Dark mode locks the side menu to the single default color, so a color
        // change is only accepted while the account is in light mode.
        if ($request->user()->theme_mode === 'dark') {
            return response()->json([
                'ok' => false,
                'message' => 'Side menu colors are locked in dark mode.',
            ], 422);
        }

        $request->user()->update(['sidebar_theme' => $theme]);

        return response()->json(['ok' => true, 'theme' => $theme]);
    })->name('settings.ui');

    // Display theme (light / dark) — every signed-in role may choose, and the
    // choice is saved to the account so it stays consistent on every page.
    Route::post('/settings/theme', function (Request $request) {
        $mode = (string) $request->input('mode');

        if (! in_array($mode, ['light', 'dark'], true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid display theme.'], 422);
        }

        $request->user()->update(['theme_mode' => $mode]);

        return response()->json(['ok' => true, 'mode' => $mode]);
    })->name('settings.theme');

    // Web Push (outside-the-system notifications) — every signed-in role may
    // opt this device in from Settings. With no VAPID keys configured the
    // public key returns empty and the frontend never offers the toggle.
    Route::get('/push/public-key', [PushSubscriptionController::class, 'publicKey'])
        ->name('push.public-key');
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'subscribe'])
        ->name('push.subscribe')->middleware('throttle:30,1');
    Route::post('/push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe'])
        ->name('push.unsubscribe')->middleware('throttle:30,1');

    // Save / unsave a product for later — every signed-in role may keep their
    // own list. There is no saved-products page yet; the toggle just syncs the
    // bookmark state shown on the product cards.
    Route::post('/products/{product}/save', [ProductController::class, 'toggleSave'])
        ->name('products.save');

    // Centralized Dashboard Redirector
    Route::get('/dashboard', function () {
        $role = Auth::user()->role;

        return match ($role) {
            'admin' => redirect()->route('dashboard.admin'),
            'store_owner' => redirect()->route('store.dashboard'),
            'farmer' => redirect()->route('dashboard.farmer'),
            default => abort(403, 'Unauthorized role access.'),
        };
    })->name('dashboard.redirect');

    /*
    |--------------------------------------------------------------------------
    | Farmer Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('farmer')->name('farmer.')->middleware('role:farmer')->group(function () {
        Route::get('/dashboard', [FarmerDashboardController::class, 'index'])->name('dashboard'); // referenced as dashboard.farmer or farmer.dashboard

        // Crops Management
        Route::get('/crops', [FarmerCropController::class, 'index'])->name('crops.index');
        Route::post('/crops', [FarmerCropController::class, 'store'])->name('crops.store');
        Route::put('/crops/{farmerCrop}', [FarmerCropController::class, 'update'])->name('crops.update');
        Route::delete('/crops/{farmerCrop}', [FarmerCropController::class, 'destroy'])->name('crops.destroy');
        Route::put('/crops-default-land', [FarmerCropController::class, 'updateDefaultLand'])->name('crops.default-land');

        // Recommendations
        Route::get('/recommendations', [ProductController::class, 'recommendations'])->name('recommendations.index');

        // AI Chat
        Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
        Route::post('/ai-chat', [AiChatController::class, 'send'])->name('ai-chat.send');
        Route::post('/ai-chat/avatar', [AiChatController::class, 'updateAvatar'])->name('ai-chat.avatar');
        Route::post('/ai-chat/suggestions', [AiChatController::class, 'suggestions'])->name('ai-chat.suggestions');
        Route::get('/ai-chat/histories', [AiChatController::class, 'histories'])->name('ai-chat.histories');
        Route::get('/ai-chat/histories/{chatHistory}', [AiChatController::class, 'history'])->name('ai-chat.history.show');
        Route::post('/ai-chat/histories', [AiChatController::class, 'saveHistory'])->name('ai-chat.history.save');
        Route::delete('/ai-chat/histories/{chatHistory}', [AiChatController::class, 'destroyHistory'])->name('ai-chat.history.destroy');

        // Crop batch detail
        Route::get('/crops/{farmerCrop}', [FarmerCropController::class, 'show'])
            ->name('crops.show')
            ->whereNumber('farmerCrop');

        // Supported Crops Directory
        Route::get('/crops-directory', [CropsDirectoryController::class, 'index'])->name('crops-directory.index');

        // Products & Store Browsing
        Route::get('/products/browse', [ProductController::class, 'browse'])->name('products.browse');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/stores', [StoreDirectoryController::class, 'index'])->name('stores.index');
        // One store's own page: its details plus every product it lists.
        // The directory card's Browse Products button and its "See all" tile
        // both land here, so a farmer is never sent to a mixed catalog.
        Route::get('/stores/{store}', [StoreDirectoryController::class, 'show'])
            ->name('stores.show')
            ->whereNumber('store');

        // Top Quoted Products — ranked by real farmer demand (quotation count)
        Route::get('/top-quoted', [ProductController::class, 'topQuoted'])->name('top-quoted.index');

        // Quotations
        Route::get('/quotations', [QuotationController::class, 'farmerIndex'])->name('quotations.index');
        Route::get('/quotations/archive', [QuotationController::class, 'farmerArchive'])->name('quotations.archive');
        Route::get('/quotations/request', [QuotationController::class, 'farmerCreate'])->name('quotations.request');
        Route::get('/quotations/products', [QuotationController::class, 'farmerProducts'])->name('quotations.products');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'farmerShow'])->name('quotations.show');
        // Itemized line-by-line breakdown — a dedicated subpage so the
        // overview page stays a plain-language snapshot (see farmerShow).
        Route::get('/quotations/{quotation}/breakdown', [QuotationController::class, 'farmerBreakdown'])
            ->name('quotations.breakdown')
            ->whereNumber('quotation');
        Route::post('/quotations/{quotation}/ai-overview', [QuotationController::class, 'farmerAiOverview'])->name('quotations.ai-overview');
        Route::post('/quotations', [QuotationController::class, 'farmerStore'])->name('quotations.store');
        Route::delete('/quotations/{quotation}', [QuotationController::class, 'farmerDestroy'])->name('quotations.destroy');

        // Activity Log
        Route::get('/activity', [QuotationController::class, 'farmerActivity'])->name('activity.index');

        // Harvest Reports (shared with admin — same harvest_reports table).
        // List page shielded (->middleware('shield')); add the same token to
        // any route below to extend coverage, delete it to drop coverage.
        Route::get('/harvests', [HarvestReportController::class, 'farmerIndex'])
            ->name('harvests.index')
            ->middleware('shield');
        Route::get('/harvests/create', [HarvestReportController::class, 'farmerCreate'])->name('harvests.create');
        // Estimate vs Actual — planting estimate against filed yield.
        Route::get('/harvests/compare', [HarvestReportController::class, 'farmerCompare'])
            ->name('harvests.compare');
        Route::post('/harvests', [HarvestReportController::class, 'store'])->name('harvests.store');
        Route::delete('/harvests/{harvestReport}', [HarvestReportController::class, 'destroy'])->name('harvests.destroy');

        // Association calendar (read-only for farmers)
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

        // Notifications
        Route::get('/notifications', [FarmerNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [FarmerNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{activityLog}/read', [FarmerNotificationController::class, 'markRead'])->name('notifications.read');

        // Settings (UI preferences)
        Route::get('/settings', function () {
            return view('farmer.settings.index');
        })->name('settings.index');
    });

    // Explicit fallback alias for dashboard.farmer reference in layout
    Route::get('/farmer/dashboard', [FarmerDashboardController::class, 'index'])
        ->name('dashboard.farmer')->middleware('role:farmer');

    /*
    |--------------------------------------------------------------------------
    | Store Owner Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('store')->name('store.')->middleware('role:store_owner')->group(function () {
        Route::get('/dashboard', [ProductController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/top-performers', [ProductController::class, 'performance'])->name('dashboard.performance');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{product}/update-stock', [InventoryController::class, 'updateStock'])->name('inventory.update');
        Route::get('/quotations', [QuotationController::class, 'storeIndex'])->name('quotations.index');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'storeShow'])->name('quotations.show');
        Route::post('/quotations/ai-overview', [QuotationController::class, 'storeAiOverview'])->name('quotations.ai-overview');
        Route::get('/storefront', [ProductController::class, 'storefront'])->name('storefront.index');

        // Activity history
        Route::get('/activity', [StoreOwnerActivityController::class, 'index'])->name('activity.index');

        // Notifications — cost estimates that involve this store's products
        Route::get('/notifications', [StoreOwnerActivityController::class, 'notifications'])->name('notifications.index');
        Route::post('/notifications/read-all', [StoreOwnerActivityController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{activityLog}/read', [StoreOwnerActivityController::class, 'markRead'])->name('notifications.read');

        // Settings (UI preferences)
        Route::get('/settings', function () {
            return view('store.settings.index');
        })->name('settings.index');

        // DSS Input Formulas (rule matrix management)
        Route::get('/formulas', [FormulaController::class, 'index'])->name('formulas.index');
        Route::get('/formulas/create', [FormulaController::class, 'create'])->name('formulas.create');
        Route::post('/formulas', [FormulaController::class, 'store'])->name('formulas.store');
        Route::get('/formulas/{formula}/edit', [FormulaController::class, 'edit'])->name('formulas.edit');
        Route::put('/formulas/{formula}', [FormulaController::class, 'update'])->name('formulas.update');
        Route::delete('/formulas/{formula}', [FormulaController::class, 'destroy'])->name('formulas.destroy');

        // AI Demand Assistant (chat)
        Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
        Route::post('/ai-chat', [AiChatController::class, 'send'])->name('ai-chat.send');
        Route::post('/ai-chat/avatar', [AiChatController::class, 'updateAvatar'])->name('ai-chat.avatar');
        Route::post('/ai-chat/suggestions', [AiChatController::class, 'suggestions'])->name('ai-chat.suggestions');
        Route::get('/ai-chat/histories', [AiChatController::class, 'histories'])->name('ai-chat.histories');
        Route::get('/ai-chat/histories/{chatHistory}', [AiChatController::class, 'history'])->name('ai-chat.history.show');
        Route::post('/ai-chat/histories', [AiChatController::class, 'saveHistory'])->name('ai-chat.history.save');
        Route::delete('/ai-chat/histories/{chatHistory}', [AiChatController::class, 'destroyHistory'])->name('ai-chat.history.destroy');

        // Product management routes
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');

        // Bulk import from CSV / Excel. Declared before /products/{product} so
        // the literal path is not captured as a product id.
        Route::get('/products/import', [ProductImportController::class, 'create'])->name('products.import');
        Route::post('/products/import/preview', [ProductImportController::class, 'preview'])->name('products.import.preview');
        Route::post('/products/import/revalidate', [ProductImportController::class, 'revalidate'])->name('products.import.revalidate');
        Route::post('/products/import/ai-fix', [ProductImportController::class, 'aiFix'])->name('products.import.ai-fix');
        Route::post('/products/import/confirm', [ProductImportController::class, 'confirm'])->name('products.import.confirm');
        Route::post('/products/import/cancel', [ProductImportController::class, 'cancel'])->name('products.import.cancel');
        Route::get('/products/import-template/{format}', [ProductImportController::class, 'template'])->name('products.import.template');

        // Archive — soft-deleted products. Literal path declared before
        // /products/{product} so "trash" is never read as a product id.
        Route::get('/products/trash', [ProductController::class, 'trash'])->name('products.trash');
        Route::post('/products/archive-bulk', [ProductController::class, 'archiveBulk'])->name('products.archive-bulk');
        Route::post('/products/{product}/restore', [ProductController::class, 'restore'])
            ->withTrashed()->name('products.restore');
        Route::delete('/products/{product}/force', [ProductController::class, 'forceDestroy'])
            ->withTrashed()->name('products.force-destroy');

        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::patch('/products/{product}/images/{image}', [ProductController::class, 'updateMainImage'])->name('products.images.main');
        Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'showAdminDashboard'])->name('dashboard');

        // ── Capture shield ── these pages carry ->middleware('shield'), which
        // blocks the browser's display-capture API on them and shows the blank
        // app-switcher cover while the page is in the background. Add the token
        // to any route to opt it in, delete it to opt it out — that's the whole
        // switch (see app/Http/Middleware/CaptureShield.php).

        // ── Members ──────────────────────────────────────────────────
        // The people themselves, held apart from the accounts they sign
        // in with. Manual entry, CSV/XLSX uploads and OCR scans all land
        // here, so this list is the single source of truth for who exists.
        Route::get('/members', [AdminMemberController::class, 'index'])
            ->name('members.index')
            ->middleware('shield');
        Route::post('/members/archive', [AdminMemberController::class, 'archiveBulk'])
            ->name('members.archive');
        Route::get('/members/create', [AdminMemberController::class, 'create'])->name('members.create');
        Route::post('/members', [AdminMemberController::class, 'store'])->name('members.store');
        Route::get('/members/{member}/edit', [AdminMemberController::class, 'edit'])
            ->name('members.edit')
            ->middleware('shield');
        Route::put('/members/{member}', [AdminMemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{member}', [AdminMemberController::class, 'destroy'])->name('members.destroy');

        // Bulk intake for the list from CSV / XLSX files. Writes members,
        // never accounts — invitations only go out when an account is made.
        Route::get('/members/import', [AdminMemberImportController::class, 'create'])
            ->name('members.import');
        Route::post('/members/import/preview', [AdminMemberImportController::class, 'preview'])
            ->name('members.import.preview');
        Route::post('/members/import/revalidate', [AdminMemberImportController::class, 'revalidate'])
            ->name('members.import.revalidate');
        Route::post('/members/import/confirm', [AdminMemberImportController::class, 'confirm'])
            ->name('members.import.confirm');
        Route::post('/members/import/cancel', [AdminMemberImportController::class, 'cancel'])
            ->name('members.import.cancel');

        // Optional OCR path: scan a JPG/PNG member list via Tesseract.
        // Records only ever become members after explicit per-row approval.
        Route::post('/members/import/ocr', [AdminMemberImportController::class, 'previewOcr'])
            ->name('members.import.ocr');
        // Re-validate the cells the administrator edited in the review table.
        Route::post('/members/import/ocr/revalidate', [AdminMemberImportController::class, 'revalidateOcr'])
            ->name('members.import.ocr.revalidate');
        Route::post('/members/import/ocr/confirm', [AdminMemberImportController::class, 'confirmOcr'])
            ->name('members.import.ocr.confirm');

        // The archive of every file this importer was ever given — CSV/XLSX
        // and the photographed forms alike — so the original stays reachable
        // years later. Declared with the literal /members/... paths above so
        // it can never be shadowed by a {member} binding.
        Route::get('/members/uploads', [AdminImportUploadController::class, 'members'])
            ->name('members.uploads');
        Route::get('/members/uploads/{importUpload}/download', [AdminImportUploadController::class, 'downloadMembers'])
            ->name('members.uploads.download');
        // Scans (pictures) render inside the page's viewer instead of
        // downloading — spreadsheets have no preview route at all.
        Route::get('/members/uploads/{importUpload}/preview', [AdminImportUploadController::class, 'previewMembers'])
            ->name('members.uploads.preview');

        // ── System accounts ─────────────────────────────────────────
        // Every account that can sign in. Creation opens on an account-type
        // chooser: farmers from a member picked off the list, store owners
        // from the store form, administrators from a typed form.
        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('users.index')
            ->middleware('shield');
        Route::post('/users/archive', [AdminUserController::class, 'archiveBulk'])
            ->name('users.archive');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        // Administrator creation carries the same four controls as granting
        // admin to an existing account: step-up password, explicit consent,
        // throttle, audit row (see AdminUserController::storeAdmin).
        Route::post('/users/create/admin', [AdminUserController::class, 'storeAdmin'])
            ->middleware('throttle:admin-grant')
            ->name('users.storeAdmin');
        Route::post('/users/create/admin/request-key', [AdminUserController::class, 'requestAdminCreationKey'])
            ->middleware('throttle:admin-grant')
            ->name('users.requestAdminCreationKey');
        Route::post('/users/create/admin/verify-key', [AdminUserController::class, 'verifyAdminCreationKey'])
            ->middleware('throttle:admin-grant')
            ->name('users.verifyAdminCreationKey');
        // One account's profile — same personal data as the registry above.
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])
            ->name('users.edit')
            ->middleware('shield');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/resend-invitation', [AdminUserController::class, 'resend'])
            ->name('users.resend');
        Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])
            ->name('users.approve');
        Route::post('/users/{user}/reject', [AdminUserController::class, 'reject'])
            ->name('users.reject');

        // Granting administrator access. Deliberately NOT folded into the
        // profile form above: it re-checks the acting admin's own password,
        // is throttled per account, and writes an audit entry, so an
        // unattended session cannot quietly mint a second administrator.
        Route::post('/users/{user}/promote', [AdminUserController::class, 'promote'])
            ->whereNumber('user')
            ->middleware('throttle:admin-grant')
            ->name('users.promote');

        Route::get('/approvals', [AdminUserController::class, 'approvals'])
            ->name('approvals.index');

        // Accredited local supply store directory (Users → Stores tab).
        // Store owners are outsiders — registered here by an administrator,
        // never created from the members list — and each store's legal
        // papers are filed against it one document at a time.
        Route::get('/stores', [AdminStoreController::class, 'index'])
            ->name('stores.index')
            ->middleware('shield');
        Route::get('/stores/create', [AdminStoreController::class, 'create'])->name('stores.create');
        Route::post('/stores', [AdminStoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/{store}/edit', [AdminStoreController::class, 'edit'])
            ->whereNumber('store')
            ->name('stores.edit')
            ->middleware('shield');
        Route::put('/stores/{store}', [AdminStoreController::class, 'update'])
            ->whereNumber('store')
            ->name('stores.update');
        Route::delete('/stores/{store}', [AdminStoreController::class, 'destroy'])
            ->whereNumber('store')
            ->name('stores.destroy');
        Route::post('/stores/{store}/documents', [AdminStoreController::class, 'storeDocument'])
            ->whereNumber('store')
            ->name('stores.documents.store');
        Route::get('/stores/{store}/documents/{document}/download', [AdminStoreController::class, 'downloadDocument'])
            ->whereNumber('store')
            ->name('stores.documents.download');
        Route::delete('/stores/{store}/documents/{document}', [AdminStoreController::class, 'destroyDocument'])
            ->whereNumber('store')
            ->name('stores.documents.destroy');

        Route::get('/quotations', [QuotationController::class, 'adminIndex'])->name('quotations.index');
        Route::get('/quotations/products', [QuotationController::class, 'adminProducts'])->name('quotations.products');
        Route::get('/activity', [QuotationController::class, 'adminActivity'])->name('activity.index');

        // Notifications — association-wide events for admins
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [AdminNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{activityLog}/read', [AdminNotificationController::class, 'markRead'])->name('notifications.read');
        Route::get('/analytics', function () {
            // Grouped under the new "Analytics" category — the Demand Trends tab
            // lives on /admin/quotations. Keep this URL redirecting so older
            // links (dashboard KPI cards, breadcrumbs) still land on a useful page.
            return redirect()->route('admin.quotations.index');
        })->name('analytics.index');
        // The formula matrix used to live here. Formulas are no longer browsed
        // as a standalone list — they will be attached to each product's own
        // info — so this URL lands on the catalog instead.
        Route::redirect('/analytics/matrix', '/admin/operations/catalog')->name('analytics.matrix');
        Route::get('/settings', function () {
            return view('admin.settings.index');
        })->name('settings.index');
        Route::get('/settings/general', function () {
            return view('admin.settings.general');
        })->name('settings.general');
        Route::get('/archive', [\App\Http\Controllers\AdminArchiveController::class, 'index'])
            ->name('archive');
        Route::post('/archive/users/{user}/restore', [\App\Http\Controllers\AdminArchiveController::class, 'restoreUser'])
            ->name('archive.users.restore');
        Route::post('/archive/members/{member}/restore', [\App\Http\Controllers\AdminArchiveController::class, 'restoreMember'])
            ->name('archive.members.restore');
        Route::get('/settings/seasons', function () {
            return view('admin.settings.seasons');
        })->name('settings.seasons');
        Route::get('/settings/interface', function () {
            return view('admin.settings.interface');
        })->name('settings.interface');
        Route::post('/settings/interface', function (Request $request) {
            $data = $request->validate([
                'yield_unit' => ['required', 'in:auto,mt,kg,cavan'],
                'yield_decimals' => ['required', 'integer', 'min:0', 'max:2'],
                'yield_number_separator' => ['required', 'in:comma,period,none'],
                'yield_zero_dash' => ['sometimes', 'boolean'],
            ]);

            $separators = ['comma' => ',', 'period' => '.', 'none' => ''];

            SettingsHelper::set('yield_unit', $data['yield_unit']);
            SettingsHelper::set('yield_decimals', (string) $data['yield_decimals']);
            SettingsHelper::set('yield_number_separator', $separators[$data['yield_number_separator']]);
            SettingsHelper::set('yield_zero_dash', ($data['yield_zero_dash'] ?? false) ? '1' : '0');

            return response()->json(['ok' => true, 'preview' => SettingsHelper::formatYield(2.45)]);
        })->name('settings.interface.update');
        // Weather Log — a dated journal of typhoons, floods, droughts and the
        // like. Separate from the preference closures above because it owns rows
        // in weather_logs that the harvest AI analysis reads.
        Route::get('/settings/weather', [AdminWeatherLogController::class, 'index'])
            ->name('settings.weather');
        Route::post('/settings/weather', [AdminWeatherLogController::class, 'store'])
            ->name('settings.weather.store');
        Route::delete('/settings/weather/{weatherLog}', [AdminWeatherLogController::class, 'destroy'])
            ->name('settings.weather.destroy');
        // ── Crops & Inputs (grouped pages ──────────────────────────
        // The standalone decision-formula matrix page is gone: formulas will
        // be maintained alongside each product's own info instead of in a
        // separate list. Old links land on the catalog.
        Route::get('/operations', fn () => redirect()->route('admin.operations.catalog'))->name('operations.index');
        Route::get('/operations/formulas', fn () => redirect()->route('admin.operations.catalog'))->name('operations.formulas');
        Route::get('/operations/catalog', function () {
            $products = Product::with('category')->get();

            $productStats = [
                'total' => $products->count(),
                'active' => $products->where('status', 'active')->count(),
                'out' => $products->filter(fn ($p) => $p->status === 'out_of_stock' || (int) $p->stock_quantity <= 0)->count(),
            ];

            $productsByCategory = Product::join('categories', 'products.category_id', '=', 'categories.id')
                ->selectRaw('categories.name as category, COUNT(*) as count')
                ->groupBy('categories.name')
                ->orderByDesc('count')
                ->get();

            // Demand snapshot: products ranked by how many distinct farmer
            // quotations include them — a real-intent signal, not opinions.
            $topQuoted = DB::table('quotation_items')
                ->join('products', 'quotation_items.product_id', '=', 'products.id')
                ->select(
                    'quotation_items.product_id',
                    'products.name',
                    DB::raw('COUNT(DISTINCT quotation_items.quotation_id) as quote_count')
                )
                ->groupBy('quotation_items.product_id', 'products.name')
                ->orderByDesc('quote_count')
                ->take(12)
                ->get();

            return view('admin.operations.catalog', compact('productStats', 'productsByCategory', 'topQuoted'));
        })->name('operations.catalog');
        Route::get('/operations/seasons', function () {
            $climate = SeasonHelper::getClimateInfo();

            $formulaCounts = DecisionFormula::selectRaw('season, COUNT(*) as count')
                ->groupBy('season')->get()->pluck('count', 'season');
            $cropCounts = FarmerCrop::selectRaw('season, COUNT(*) as count')
                ->groupBy('season')->get()->pluck('count', 'season');
            $quotationCounts = Quotation::selectRaw('season, COUNT(*) as count')
                ->groupBy('season')->get()->pluck('count', 'season');

            $seasons = collect(['wet' => [
                'label' => 'Wet Season',
                'months' => 'June – January',
                'current' => $climate['season'] === 'wet',
                'icon' => 'cloud-rain',
                'typical_weather' => $climate['season'] === 'wet' ? $climate['typical_weather'] : 'Strong southwest monsoon (Habagat) with heavy rainfall and typhoons — the main cropping window.',
                'recommendation' => $climate['season'] === 'wet' ? $climate['recommendation'] : 'Use flood-tolerant rice varieties. Ensure proper drainage systems.',
                'formulas' => (int) ($formulaCounts['wet'] ?? 0),
                'crops' => (int) ($cropCounts['wet'] ?? 0),
                'quotations' => (int) ($quotationCounts['wet'] ?? 0),
            ], 'dry' => [
                'label' => 'Dry Season',
                'months' => 'February – May',
                'current' => $climate['season'] === 'dry',
                'icon' => 'sun',
                'typical_weather' => $climate['season'] === 'dry' ? $climate['typical_weather'] : 'High temperatures, very low rainfall. Requires irrigation for crops.',
                'recommendation' => $climate['season'] === 'dry' ? $climate['recommendation'] : 'Drought-resistant varieties recommended. Ensure adequate irrigation.',
                'formulas' => (int) ($formulaCounts['dry'] ?? 0),
                'crops' => (int) ($cropCounts['dry'] ?? 0),
                'quotations' => (int) ($quotationCounts['dry'] ?? 0),
            ]]);

            return view('admin.operations.seasons', compact('climate', 'seasons'));
        })->name('operations.seasons');

        // ── Planting Requests ─────────────────────────────────────
        // Farmer crop batches await admin confirmation before their growth
        // stages unlock.
        Route::get('/plantings', [AdminPlantingController::class, 'index'])
            ->name('plantings.index');
        Route::post('/plantings/{farmerCrop}/confirm', [AdminPlantingController::class, 'confirm'])
            ->name('plantings.confirm');
        Route::post('/plantings/{farmerCrop}/reject', [AdminPlantingController::class, 'reject'])
            ->name('plantings.reject');

        // ── Association calendar ──────────────────────────────────
        // Shared schedule: the admin adds events (never on past dates),
        // farmers read them. Store accounts have no routes here.
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::post('/calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store');
        Route::put('/calendar/events/{event}', [CalendarController::class, 'update'])->name('calendar.events.update');
        Route::delete('/calendar/events/{event}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy');

        // ── Analytics & Reports (grouped pages) ────────────────────
        // Expenses — what farming actually cost, grouped the way an officer
        // reads it: crop, season, year. "Planned" (the estimate) is the total
        // of a store quotation linked to the report; the importer and the
        // manual form both leave that link null, so with nothing quoted the
        // page must not draw budget/variance conclusions from ₱0.
        Route::get('/analytics/expenses', function () {
            $reports = HarvestReport::with('quotation')->get();

            $planned = fn ($set) => $set->sum(
                fn ($r) => (float) ($r->quotation?->estimated_total_cost ?? 0)
            );

            $totals = [
                'planned' => $planned($reports),
                'actual' => $reports->sum(fn ($r) => (float) $r->actual_total_expenses),
                'count' => $reports->count(),
                'yield' => $reports->sum(fn ($r) => (float) $r->actual_yield_metric_tons),
                'area' => $reports->sum(fn ($r) => (float) $r->land_area),
            ];
            $totals['variance'] = $totals['actual'] - $totals['planned'];

            // No quotation has ever been linked to a report → there is no
            // budget to compare against, and "variance" would just echo
            // the whole spend back as "over budget".
            $hasBudget = $totals['planned'] > 0;

            $byCrop = $reports->groupBy('crop_type')->map(fn ($group) => [
                'crop' => $group->first()->crop_type,
                'count' => $group->count(),
                'planned' => $planned($group),
                'actual' => $group->sum(fn ($r) => (float) $r->actual_total_expenses),
                'area' => $group->sum(fn ($r) => (float) $r->land_area),
            ])->sortByDesc('actual')->values();

            $bySeason = $reports->groupBy('season')->map(fn ($group) => [
                'season' => ucfirst($group->first()->season ?? 'wet'),
                'count' => $group->count(),
                'planned' => $planned($group),
                'actual' => $group->sum(fn ($r) => (float) $r->actual_total_expenses),
                'yield' => $group->sum(fn ($r) => (float) $r->actual_yield_metric_tons),
            ])->sortByDesc('actual')->values();

            // The imported history spans many years of paper records —
            // grouping by the year the harvest happened keeps a decade
            // readable in one scan.
            $byYear = $reports
                ->groupBy(fn ($r) => $r->harvest_date?->format('Y') ?? 'Unknown')
                ->map(fn ($group, $year) => [
                    'year' => (string) $year,
                    'count' => $group->count(),
                    'planned' => $planned($group),
                    'actual' => $group->sum(fn ($r) => (float) $r->actual_total_expenses),
                    'yield' => $group->sum(fn ($r) => (float) $r->actual_yield_metric_tons),
                ])
                ->sortKeys()
                ->values();

            return view('admin.analytics.expenses', compact('totals', 'byCrop', 'bySeason', 'byYear', 'hasBudget'));
        })->name('analytics.expenses');

        // ── Settings (grouped pages) ───────────────────────────────
        // Real audit trail: every row comes from activity_logs.
        Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');

        // Harvest Reports — admin view of every farmer's harvest reports.
        // (->middleware('shield') = the per-page capture switch; drop the token
        // from a route to take the page back out of shielded scope.)
        Route::get('/harvests', [HarvestReportController::class, 'adminIndex'])
            ->name('harvests.index')
            ->middleware('shield');

        // All Harvest Reports — the full, filterable table, split out from the
        // landing page so that page can surface summary cards instead.
        Route::get('/harvests/reports', [HarvestReportController::class, 'reportsIndex'])
            ->name('harvests.reports')
            ->middleware('shield');

        // Removing wrong records from the table: the officer ticks rows in
        // the list (or the Uploads page removes a whole file's worth) and
        // confirms — declared as a literal so it never collides with a
        // future /harvests/reports/{report} route.
        Route::post('/harvests/reports/remove', [HarvestReportController::class, 'removeRecords'])
            ->name('harvests.reports.remove');
        Route::post('/harvests/reports/archive', [HarvestReportController::class, 'archiveRecords'])
            ->name('harvests.reports.archive');
        Route::post('/archive/harvest-reports/{harvestReport}/restore', [HarvestReportController::class, 'restoreArchived'])
            ->name('archive.harvest-reports.restore');

        // Bulk import of the association's historical harvest records from a
        // CSV / Excel file. Declared before /harvests/reports so the literal
        // paths can never be shadowed.
        Route::get('/harvests/import', [AdminHarvestImportController::class, 'create'])
            ->name('harvests.import');
        Route::post('/harvests/import/preview', [AdminHarvestImportController::class, 'preview'])
            ->name('harvests.import.preview');
        Route::post('/harvests/import/revalidate', [AdminHarvestImportController::class, 'revalidate'])
            ->name('harvests.import.revalidate');
        Route::post('/harvests/import/confirm', [AdminHarvestImportController::class, 'confirm'])
            ->name('harvests.import.confirm');
        Route::post('/harvests/import/cancel', [AdminHarvestImportController::class, 'cancel'])
            ->name('harvests.import.cancel');
        Route::get('/harvests/import-template/{format}', [AdminHarvestImportController::class, 'template'])
            ->name('harvests.import.template');

        // The archive of every historical harvest file this importer was ever
        // given, kept so the original stays reachable years later.
        Route::get('/harvests/uploads', [AdminImportUploadController::class, 'harvest'])
            ->name('harvests.uploads');
        Route::get('/harvests/uploads/{importUpload}/download', [AdminImportUploadController::class, 'downloadHarvest'])
            ->name('harvests.uploads.download');
        // Same deal as the members list: pictures view in-page, sheets download.
        Route::get('/harvests/uploads/{importUpload}/preview', [AdminImportUploadController::class, 'previewHarvest'])
            ->name('harvests.uploads.preview');
        // Wrong file? Take back everything it imported, in one action.
        // The upload itself stays archived for a re-import.
        Route::post('/harvests/uploads/{importUpload}/remove-records', [AdminImportUploadController::class, 'removeHarvestRecords'])
            ->name('harvests.uploads.remove-records');

        // AI Harvest Insights — analytics subpage over the harvest_reports table.
        // Declared before the index so the literal path can never be shadowed.
        Route::get('/harvests/ai', [HarvestReportController::class, 'aiIndex'])
            ->name('harvests.ai');

        // One chat turn. Returns JSON so the chat keeps its own loading and
        // error state instead of round tripping the whole page.
        Route::post('/harvests/ai/ask', [HarvestReportController::class, 'aiAsk'])
            ->name('harvests.ai.ask');
        Route::get('/harvests/ai/histories', [HarvestReportController::class, 'adminAiHistories'])
            ->name('harvests.ai.histories');
        Route::get('/harvests/ai/histories/{chatHistory}', [HarvestReportController::class, 'adminAiHistory'])
            ->name('harvests.ai.history.show');
        Route::post('/harvests/ai/histories', [HarvestReportController::class, 'saveAdminAiHistory'])
            ->name('harvests.ai.history.save');
        Route::delete('/harvests/ai/histories/{chatHistory}', [HarvestReportController::class, 'destroyAdminAiHistory'])
            ->name('harvests.ai.history.destroy');

        // Estimate vs Actual — planting estimate against reported yield.
        Route::get('/harvests/compare', [HarvestReportController::class, 'adminCompare'])
            ->name('harvests.compare');

        // Full harvest history — the wet-vs-dry trend as a page of its own,
        // opened from the landing page's Full view button (it used to be a
        // dialog there). Breadcrumbed back to the landing page and kept out
        // of the sidebar on purpose: config/admin-nav.php stays as it is.
        Route::get('/harvests/history', [HarvestReportController::class, 'adminHistory'])
            ->name('harvests.history')
            ->middleware('shield');
    });

    // Explicit fallback alias for dashboard.admin reference in layout and AuthController
    Route::get('/admin/dashboard', [AuthController::class, 'showAdminDashboard'])
        ->name('dashboard.admin')->middleware('role:admin');
});
