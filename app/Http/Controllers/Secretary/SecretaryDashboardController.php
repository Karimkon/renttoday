<?php 

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Apartment;
use App\Models\Payment;

class SecretaryDashboardController extends Controller
{
    public function index()
{
    $tenantsCount   = Tenant::count();
    $apartmentsCount = Apartment::count();

    $paymentsThisMonth = Payment::whereMonth('month', now()->month)
                                ->whereYear('month', now()->year)
                                ->sum('amount');

    $apartments = Apartment::with('payments', 'tenant')->get();
    $paidAheadSum = Tenant::where('credit_balance', '>', 0)->sum('credit_balance');
    $paidAhead = Tenant::where('credit_balance', '>', 0)->count();


    $totalDue = 0;
    $statusCounts = [
        'paid' => 0,
        'partial' => 0,
        'unpaid' => 0,
        'empty' => 0
    ];

    foreach($apartments as $apt) {
        if(!$apt->tenant) {
            $statusCounts['empty']++;
            continue;
        }

        $totalPaid = $apt->payments->sum('amount');
        $monthsSinceStart = max(1, now()->diffInMonths($apt->created_at->copy()->startOfMonth()) + 1);
        $due = ($apt->rent * $monthsSinceStart) - $totalPaid;
        $totalDue += $due;

        $monthPayment = $apt->payments->where('month', now()->format('Y-m').'%')->sum('amount');
        if($monthPayment >= $apt->rent) $statusCounts['paid']++;
        elseif($monthPayment > 0) $statusCounts['partial']++;
        else $statusCounts['unpaid']++;
    }

    return view('secretary.dashboard', compact(
        'tenantsCount', 'apartmentsCount', 'paymentsThisMonth', 'totalDue', 'statusCounts', 'paidAhead', 'paidAheadSum',
    ));
}

}
