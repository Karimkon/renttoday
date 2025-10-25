<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Apartment;
use App\Models\Payment;
use App\Models\Landlord;
use App\Models\Expense;
use App\Models\LatePaymentFee;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Users summary
        $totalUsers = User::count();
        $totalAdmins = User::where('role','admin')->count();
        $totalFinance = User::where('role','finance')->count();
        $totalSecretary = User::where('role','secretary')->count();

        // Apartments summary
        $apartments = Apartment::with('tenant','payments','landlord')->get();
        $totalApartments = $apartments->count();
        $occupied = $apartments->whereNotNull('tenant')->count();
        $empty = $apartments->whereNull('tenant')->count();
        $occupancyRate = $totalApartments > 0 ? round(($occupied / $totalApartments) * 100, 1) : 0;

        // Tenants summary
        $tenants = Tenant::with('apartment')->get();
        $totalTenants = $tenants->count();
        $tenantsWithApt = $tenants->whereNotNull('apartment')->count();
        $tenantsWithoutApt = $tenants->whereNull('apartment')->count();
        $totalCredit = $tenants->sum('credit_balance');

        // Payments summary
        $currentMonth = Carbon::now()->format('Y-m');
        $payments = Payment::with('apartment','tenant')->orderBy('month','desc')->get();
        $totalPayments = $payments->count();
        $totalRevenue = $payments->sum('amount');
        $monthlyRevenue = $payments->where('month', 'like', $currentMonth . '%')->sum('amount');
        $pendingPayments = Payment::where('status', 'pending')->count();

        // Landlords summary
        $totalLandlords = Landlord::count();
        $landlordsWithApartments = Landlord::has('apartments')->count();

        // Financial metrics
        $currentYear = Carbon::now()->year;
        $yearlyRevenue = Payment::whereYear('month', $currentYear)->sum('amount');
        $totalExpenses = Expense::sum('amount');
        $outstandingLateFees = LatePaymentFee::where('status', 'unpaid')->sum('amount');

        // Monthly revenue chart (last 12 months)
        $months = [];
        $monthlyRevenueData = [];
        $monthlyExpenses = [];
        
        for($i = 11; $i >= 0; $i--){
            $monthDate = Carbon::now()->subMonths($i);
            $month = $monthDate->format('M Y');
            $monthStart = $monthDate->startOfMonth()->format('Y-m-d');
            $monthEnd = $monthDate->endOfMonth()->format('Y-m-d');
            
            $months[] = $month;
            $monthlyRevenueData[] = Payment::whereBetween('month', [$monthStart, $monthEnd])
                                         ->where('status', 'paid')
                                         ->sum('amount');
            $monthlyExpenses[] = Expense::whereBetween('date', [$monthStart, $monthEnd])
                                      ->sum('amount');
        }

        // Payment status distribution
        $paymentStatus = [
            'Paid' => Payment::where('status', 'paid')->count(),
            'Pending' => Payment::where('status', 'pending')->count(),
            'Failed' => Payment::where('status', 'failed')->count(),
        ];

        // Recent activity
        $recentPayments = Payment::with('tenant', 'apartment')
                               ->orderBy('created_at', 'desc')
                               ->take(8)
                               ->get();

        $recentApartments = Apartment::with('tenant', 'landlord')
                                   ->orderBy('created_at', 'desc')
                                   ->take(5)
                                   ->get();

        // Performance metrics
        $collectionEfficiency = $totalApartments > 0 ? 
            round(($occupied / $totalApartments) * 100, 1) : 0;

        return view('admin.dashboard', compact(
            'totalUsers', 'totalAdmins', 'totalFinance', 'totalSecretary',
            'totalApartments', 'occupied', 'empty', 'occupancyRate',
            'totalTenants', 'tenantsWithApt', 'tenantsWithoutApt', 'totalCredit',
            'totalPayments', 'totalRevenue', 'monthlyRevenue', 'pendingPayments',
            'totalLandlords', 'landlordsWithApartments',
            'yearlyRevenue', 'totalExpenses', 'outstandingLateFees',
            'months', 'monthlyRevenueData', 'monthlyExpenses',
            'paymentStatus', 'recentPayments', 'recentApartments',
            'collectionEfficiency'
        ));
    }
}