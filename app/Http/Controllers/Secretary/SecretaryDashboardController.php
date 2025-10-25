<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Apartment;
use App\Models\Payment;
use Carbon\Carbon;

class SecretaryDashboardController extends Controller
{
    public function index()
    {
        $tenantsCount    = Tenant::count();
        $apartmentsCount = Apartment::count();

        // Payments made THIS month
        $paymentsThisMonth = Payment::whereYear('month', now()->year)
                                    ->whereMonth('month', now()->month)
                                    ->sum('amount');

        // Tenants who have credit balance
        $paidAheadSum = Tenant::where('credit_balance', '>', 0)->sum('credit_balance');
        $paidAhead    = Tenant::where('credit_balance', '>', 0)->count();

        $apartments   = Apartment::with(['tenant', 'payments'])->get();

        $totalDue     = 0;
        $statusCounts = [
            'paid' => 0,
            'partial' => 0,
            'unpaid' => 0,
            'empty' => 0
        ];

        foreach($apartments as $apt) {
            if(!$apt->tenant) {
                $apt->status = 'empty';
                $apt->dueAmount = 0;
                $statusCounts['empty']++;
                continue;
            }

            // FIXED: Calculate total payments (ALL TIME, not just this month)
            $totalPaidHistoric = $apt->payments->sum('amount');

            // Calculate months since apartment creation
            $monthsSinceStart = max(1, now()->diffInMonths($apt->created_at->copy()->startOfMonth()) + 1);
            
            // Calculate total expected rent
            $totalExpectedRent = $apt->rent * $monthsSinceStart;
            
            // Calculate due amount (including credit)
            $apt->dueAmount = max(0, $totalExpectedRent - $totalPaidHistoric - ($apt->tenant->credit_balance ?? 0));

            // Determine status based on TOTAL payments + credit vs TOTAL expected
            $effectivePayment = $totalPaidHistoric + ($apt->tenant->credit_balance ?? 0);
            
            if($effectivePayment >= $totalExpectedRent) {
                $apt->status = 'paid';
            } elseif($effectivePayment > 0) {
                $apt->status = 'partial';
            } else {
                $apt->status = 'unpaid';
            }

            $statusCounts[$apt->status]++;
            $totalDue += $apt->dueAmount;
        }

        return view('secretary.dashboard', compact(
            'tenantsCount',
            'apartmentsCount',
            'paymentsThisMonth',
            'statusCounts',
            'totalDue',
            'paidAhead',
            'paidAheadSum'
        ));
    }
}