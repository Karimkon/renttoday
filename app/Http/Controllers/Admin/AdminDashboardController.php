<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Apartment;
use App\Models\Payment;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Users summary
        $totalUsers = User::count();
        $totalAdmins = User::where('role','admin')->count();
        $totalStaff = User::whereIn('role',['finance','secretary'])->count();

        // Apartments summary
        $apartments = Apartment::with('tenant','payments')->get();
        $totalApartments = $apartments->count();
        $occupied = $apartments->whereNotNull('tenant')->count();
        $empty = $apartments->whereNull('tenant')->count();

        // Tenants summary
        $tenants = Tenant::with('apartment')->get();
        $totalTenants = $tenants->count();
        $tenantsWithApt = $tenants->whereNotNull('apartment')->count();
        $tenantsWithoutApt = $tenants->whereNull('apartment')->count();
        $totalCredit = $tenants->sum('credit_balance');

        // Payments summary
        $payments = Payment::with('apartment','tenant')->orderBy('month','desc')->get();
        $totalPayments = $payments->count();
        $totalRevenue = $payments->sum('amount');

        // Monthly revenue chart (last 12 months)
        $months = [];
        $monthlyRevenue = [];
        for($i=11; $i>=0; $i--){
            $month = Carbon::now()->subMonths($i)->format('M Y');
            $months[] = $month;
            $monthlyRevenue[] = $payments->filter(fn($p) => Carbon::parse($p->month)->format('M Y') === $month)
                                         ->sum('amount');
        }

        // Apartment occupancy chart
        $apartmentStatus = [
            'Occupied' => $occupied,
            'Empty' => $empty
        ];

        return view('admin.dashboard', compact(
            'totalUsers','totalAdmins','totalStaff',
            'totalApartments','occupied','empty',
            'totalTenants','tenantsWithApt','tenantsWithoutApt','totalCredit',
            'totalPayments','totalRevenue','months','monthlyRevenue','apartmentStatus','payments'
        ));
    }
}
