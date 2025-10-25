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
use App\Http\Controllers\Tenant\TenantDashboardController;


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


Route::get('/tenant/login', fn() => view('tenant.auth.login'))->name('tenant.login');
Route::post('/tenant/login', function(Request $request){
    $request->validate([
        'email'=>'required|email',
        'password'=>'required',
    ]);

    if(Auth::attempt([
        'email'=>$request->email,
        'password'=>$request->password,
        'role'=>'tenant'
    ])){
        $request->session()->regenerate();
        return redirect()->route('tenant.dashboard');
    }

    return back()->with('error','Invalid credentials');
})->name('tenant.login.submit');

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
    Route::resource('tenants', \App\Http\Controllers\Admin\AdminTenantController::class);
});


Route::middleware(['auth','role:finance'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/dashboard', [FinanceDashboardController::class,'index'])->name('dashboard'); 
});

Route::middleware(['auth','role:secretary'])->prefix('secretary')->name('secretary.')->group(function () {
    Route::get('/dashboard', [SecretaryDashboardController::class,'index'])->name('dashboard');
    
    Route::resource('tenants', TenantController::class);

    // Apartments CRUD
    Route::resource('apartments', ApartmentController::class);

    Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');

    Route::resource('payments', PaymentController::class);

    

    // Secretary Invoices
Route::prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/', [App\Http\Controllers\Secretary\InvoiceController::class, 'index'])->name('index');          // list invoices
    Route::get('/create', [App\Http\Controllers\Secretary\InvoiceController::class, 'create'])->name('create'); // show create form
    Route::post('/store', [App\Http\Controllers\Secretary\InvoiceController::class, 'store'])->name('store');   // save invoice
    Route::get('/{invoice}', [App\Http\Controllers\Secretary\InvoiceController::class, 'show'])->name('show');   // view invoice
    Route::get('/{invoice}/pdf', [App\Http\Controllers\Secretary\InvoiceController::class, 'pdf'])->name('pdf'); // download PDF
    Route::post('/{invoice}/mark-paid', [App\Http\Controllers\Secretary\InvoiceController::class,'markPaid'])->name('markPaid'); // mark paid
    Route::delete('/{invoice}', [App\Http\Controllers\Secretary\InvoiceController::class, 'destroy'])->name('destroy');

});


});

Route::middleware(['auth','role:tenant'])->prefix('tenant')->name('tenant.')->group(function(){
    Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('dashboard');
    // Tenant payments
    Route::get('/payments', [App\Http\Controllers\Tenant\TenantPaymentController::class, 'index'])->name('payments.index');
    Route::match(['GET','POST'], '/payments/{payment}/pay', [App\Http\Controllers\Tenant\TenantPaymentController::class, 'pay'])
    ->name('payments.pay');

    Route::match(['GET','POST'], '/payments/callback', [App\Http\Controllers\Tenant\TenantPaymentController::class, 'callback'])
        ->name('payments.callback');
});

