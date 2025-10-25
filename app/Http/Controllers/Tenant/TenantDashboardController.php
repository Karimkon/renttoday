<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Payment;

class TenantDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if (!$tenant) {
            abort(404, 'Tenant profile not found.');
        }

        // Apartment info
        $apartment = $tenant->apartment;

        // Payments for last 6 months
        $payments = Payment::where('tenant_id', $tenant->id)
                    ->orderBy('month', 'desc')
                    ->take(6)
                    ->get();

        // Calculate next due
        $lastPayment = $payments->first();
        $nextDue = $lastPayment ? Carbon::parse($lastPayment->month)->addMonth() : null;

        // Calculate status
        $totalPaid = $payments->sum('amount') + ($tenant->credit_balance ?? 0);
        $status = 'unpaid';
        if ($apartment) {
            $monthsSinceStart = max(1, now()->diffInMonths($apartment->created_at->copy()->startOfMonth()) + 1);
            $expectedRent = $apartment->rent * $monthsSinceStart;

            if ($totalPaid >= $expectedRent) $status = 'paid';
            elseif ($totalPaid > 0) $status = 'partial';
        }

        return view('tenant.dashboard', compact(
            'tenant', 
            'apartment', 
            'payments', 
            'nextDue', 
            'status', 
            'totalPaid'
        ));
    }
}
