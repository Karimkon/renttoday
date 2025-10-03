<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Secretary\SecretaryDashboardController;
use App\Http\Controllers\Secretary\TenantController;
use App\Http\Controllers\Secretary\ApartmentController;
use App\Http\Controllers\Secretary\PaymentController;

Route::get('/', fn () => view('welcome'));

// ----------------------
// Login views per role
// ----------------------
Route::get('/admin/login', fn () => view('admin.auth.login'))->name('admin.login');
Route::get('/finance/login', fn () => view('finance.auth.login'))->name('finance.login');
Route::get('/secretary/login', fn () => view('secretary.auth.login'))->name('secretary.login');

// ----------------------
// Login submit per role
// ----------------------
Route::post('/admin/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required','email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt([
        'email' => $request->email,
        'password' => $request->password,
        'role' => 'admin',       // force admin here
    ], $request->boolean('remember'))) {

    $request->session()->regenerate();
    return redirect()->intended(route('admin.dashboard'));
}

return back()->with('error', 'Only admins can login here.');


    Auth::logout();
    return redirect()->route('admin.login')->with('error','Only admins can login here.');
})->name('admin.login.submit');


Route::post('/finance/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required','email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt([
        'email' => $request->email,
        'password' => $request->password,
        'role' => 'finance',       // force finance here
    ], $request->boolean('remember'))) {

    $request->session()->regenerate();
    return redirect()->intended(route('finance.dashboard'));
}

return back()->with('error', 'Only finances can login here.');


    Auth::logout();
    return redirect()->route('finance.login')->with('error','Only finances can login here.');
})->name('finance.login.submit');


Route::post('/secretary/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required','email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt([
        'email' => $request->email,
        'password' => $request->password,
        'role' => 'secretary',       // force secretary here
    ], $request->boolean('remember'))) {

    $request->session()->regenerate();
    return redirect()->intended(route('secretary.dashboard'));
}

return back()->with('error', 'Only secretarys can login here.');


    Auth::logout();
    return redirect()->route('secretary.login')->with('error','Only secretarys can login here.');
})->name('secretary.login.submit');


// ----------------------
// Shared logout
// ----------------------
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');


Route::middleware(['auth','role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class,'index'])->name('dashboard'); 
    Route::resource('users', App\Http\Controllers\Admin\AdminUserController::class);
    Route::resource('apartments', \App\Http\Controllers\Admin\AdminApartmentController::class);
    Route::resource('inventory', \App\Http\Controllers\Admin\InventoryController::class);
    Route::resource('payments', \App\Http\Controllers\Admin\AdminPaymentController::class);
});


Route::middleware(['auth','role:finance'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/dashboard', [FinanceDashboardController::class,'index'])->name('dashboard'); 
});

Route::middleware(['auth','role:secretary'])->prefix('secretary')->name('secretary.')->group(function () {
    Route::get('/dashboard', [SecretaryDashboardController::class,'index'])->name('dashboard');
    
    Route::resource('tenants', TenantController::class);

    // Apartments CRUD
    Route::resource('apartments', ApartmentController::class);

    Route::resource('payments', PaymentController::class);

});