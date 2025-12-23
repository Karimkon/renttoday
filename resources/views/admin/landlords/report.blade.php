@extends('admin.layouts.app')
@php
use Carbon\Carbon;
@endphp
@section('title', 'Landlord Monthly Report')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-0">RENT TODAY - MANAGEMENT AGENCY</h4>
                    <h5 class="mb-0">CLIENT'S MONTHLY REPORT FOR THE MONTH OF: {{ strtoupper($reportData['month']->format('F Y')) }}</h5>
                    <h6 class="mb-0">CLIENT'S NAME: {{ strtoupper($reportData['landlord']->name) }}</h6>
                    <h6 class="mb-0">PROPERTY LOCATION: {{ $locations->implode(' and ') }}</h6>
                    <h6 class="mb-0">COMMISSION RATE: {{ $reportData['landlord']->commission_rate }}%</h6>
                    
                    <!-- Payment Status Badge -->
                    <div class="mt-2">
                        @if($reportData['landlordPaymentStatus']['paid'])
                            <span class="badge bg-success fs-6">
                                <i class="bi bi-check-circle-fill"></i> 
                                PAYMENT SENT TO LANDLORD 
                                <small class="ms-2">({{ \Carbon\Carbon::parse($reportData['landlordPaymentStatus']['paid_at'])->format('M j, Y') }})</small>
                            </span>
                            <form method="POST" action="{{ route('admin.landlords.mark-unpaid', $reportData['landlord']) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="month" value="{{ $reportData['month']->format('Y-m') }}">
                                <button type="submit" class="btn btn-sm btn-outline-warning ms-2" 
                                        onclick="return confirm('Mark payment as unpaid?')">
                                    <i class="bi bi-x-circle"></i> Mark Unpaid
                                </button>
                            </form>
                        @else
                            <span class="badge bg-warning text-dark fs-6">
                                <i class="bi bi-clock-history"></i> 
                                PAYMENT PENDING TO LANDLORD
                            </span>
                            @if($reportData['amountDue'] > 0)
                            <form method="POST" action="{{ route('admin.landlords.mark-paid', $reportData['landlord']) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="month" value="{{ $reportData['month']->format('Y-m') }}">
                                <input type="hidden" name="amount" value="{{ $reportData['amountDue'] }}">
                                <button type="submit" class="btn btn-sm btn-outline-success ms-2">
                                    <i class="bi bi-check-circle"></i> Mark as Paid
                                </button>
                            </form>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.landlords.report.pdf', ['landlord' => $reportData['landlord']->id, 'month' => $reportData['month']->format('Y-m')]) }}" 
                       class="btn btn-primary" target="_blank">
                        <i class="bi bi-download"></i> Export PDF/Print
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @foreach($locations as $location)
                @php
                    $locationApartments = $reportData['apartments']->where('location', $location);
                    $locationTotal = 0;
                    $locationCommission = 0;
                @endphp

                <div class="table-responsive mb-4">
                    <h6 class="fw-bold">{{ strtoupper($location) }}</h6>
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th width="12%">Apartment No</th>
                                <th width="12%">Month Rate</th>
                                <th width="12%">Rent Paid</th>
                                <th width="12%">Commission</th>
                                <th width="12%">Amount</th>
                                <th width="12%">Month/s Paid</th>
                                <th width="14%">Date of payment</th>
                                <th width="14%">Next of pyt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($locationApartments as $apartment)
                                @php
                                    $reportMonth = $reportData['month']->format('Y-m');

                                    // Get ALL payments made in this month (not just covering this month)
                                    // FIX: This now correctly filters out advance payment split records
                                    $allPaymentsMadeThisMonth = $apartment->getPaymentsActuallyMadeInMonth($reportMonth);

                                    // FIX: Calculate total correctly - for advance payments use original_amount
                                    $totalAmountPaidThisMonth = $allPaymentsMadeThisMonth->sum(function($payment) {
                                        if ($payment->is_advance_payment && $payment->original_amount) {
                                            return $payment->original_amount;
                                        }
                                        return $payment->amount;
                                    });
                                    
                                    // Check if this includes advance payments
                                    $advancePaymentsThisMonth = $allPaymentsMadeThisMonth->where('is_advance_payment', true);
                                    $hasAdvanceThisMonth = $advancePaymentsThisMonth->count() > 0;
                                    
                                    // Get payments covering this month (could be from this month or previous)
                                    $paymentsCoveringThisMonth = $apartment->payments()
                                        ->where('status', 'paid')
                                        ->get()
                                        ->filter(function($payment) use ($reportMonth) {
                                            return $payment->coversMonth($reportMonth);
                                        });
                                    
                                    // Calculate total amount covering this month
                                    $totalAmountCoveringThisMonth = $paymentsCoveringThisMonth->sum(function($payment) use ($reportMonth) {
                                        return $payment->getAmountForMonth($reportMonth);
                                    });
                                    
                                    // Check if covered by previous advance
                                    $coveringAdvance = $apartment->getCoveringAdvancePayment($reportMonth);
                                    $coveredByPreviousAdvance = ($coveringAdvance !== null);
                                    
                                    // Determine what to display
                                    if (!$apartment->tenant) {
                                        // VACANT
                                        $rentPaidDisplay = 'UGX 0';
                                        $rentForCommission = 0;
                                        $amountCoveringMonth = 0;
                                        $statusDisplay = '<span class="badge bg-secondary">VACANT</span>';
                                        $statusClass = '';
                                        $nextPayment = '<span class="badge bg-secondary">VACANT</span>';
                                        $paymentDate = '-';
                                        $monthsCovered = 0;
                                    } 
                                    // Check if ANY payment was made this month (including advance)
                                    elseif ($totalAmountPaidThisMonth > 0) {
                                        // Money was ACTUALLY received this month
                                        $rentPaidDisplay = 'UGX ' . number_format($totalAmountPaidThisMonth);
                                        $rentForCommission = $totalAmountPaidThisMonth; // Commission on full amount received
                                        
                                        if ($hasAdvanceThisMonth) {
                                            // Advance payment made THIS month
                                            $advancePayment = $advancePaymentsThisMonth->first();
                                            $monthsCovered = count($advancePayment->allocated_months ?? []);
                                            if ($monthsCovered == 0) {
                                                $monthsCovered = floor($totalAmountPaidThisMonth / $apartment->rent);
                                            }
                                            
                                            if ($monthsCovered > 1) {
                                                $statusDisplay = '<span class="badge bg-success">' . $monthsCovered . ' MONTHS ADVANCE PAID</span>';
                                                $statusClass = 'text-success fw-bold';
                                                
                                                // Show which months are covered
                                                if ($advancePayment->allocated_months) {
                                                    $allocatedMonths = collect($advancePayment->allocated_months)
                                                        ->map(function($month) {
                                                            return \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y');
                                                        })
                                                        ->implode(', ');
                                                    $statusDisplay .= '<br><small class="text-muted">Covers: ' . $allocatedMonths . '</small>';
                                                }
                                            } else {
                                                $statusDisplay = '<span class="badge bg-success">PAID</span>';
                                                $statusClass = 'text-success';
                                                $monthsCovered = 1;
                                            }
                                            
                                            $paymentDate = $advancePayment->actual_payment_date 
                                                ? $advancePayment->actual_payment_date->format('jS/m/Y')
                                                : $advancePayment->paid_at->format('jS/m/Y');
                                            
                                            // Calculate next payment
                                            if ($advancePayment->allocated_months) {
                                                $allocatedMonths = collect($advancePayment->allocated_months)
                                                    ->sort()
                                                    ->values();
                                                $lastCoveredMonth = \Carbon\Carbon::createFromFormat('Y-m', $allocatedMonths->last());
                                                $nextPayment = $lastCoveredMonth->copy()->addMonth()->format('F Y');
                                            } else {
                                                $nextPayment = $reportData['month']->copy()->addMonths($monthsCovered)->format('F Y');
                                            }
                                        } else {
                                            // Regular single month payment
                                            $payment = $allPaymentsMadeThisMonth->first();
                                            $paidDirectlyToLandlord = $payment->paid_to_landlord_directly ?? false;

                                            if ($paidDirectlyToLandlord) {
                                                $statusDisplay = '<span class="badge bg-info">' . $reportData['month']->format('F') . ' PAID</span><br><small class="text-info">Direct to Landlord</small>';
                                            } else {
                                                $statusDisplay = '<span class="badge bg-success">' . $reportData['month']->format('F') . ' PAID</span>';
                                            }
                                            $statusClass = '';
                                            $monthsCovered = 1;

                                            $paymentDate = $payment->actual_payment_date
                                                ? $payment->actual_payment_date->format('jS/m/Y')
                                                : $payment->paid_at->format('jS/m/Y');
                                            $nextPayment = $reportData['month']->copy()->addMonth()->format('F Y');
                                        }
                                        
                                        $amountCoveringMonth = $totalAmountCoveringThisMonth;
                                    } 
                                                                        elseif ($coveredByPreviousAdvance) {
                                        // Covered by previous advance (no new money this month)
                                        $advanceDate = $coveringAdvance->actual_payment_date 
                                            ? $coveringAdvance->actual_payment_date->format('jS/m/Y')
                                            : $coveringAdvance->paid_at->format('jS/m/Y');
                                        
                                        // Show amount with "Advance" note instead of just "ADVANCE COVER"
                                        $rentPaidDisplay = 'UGX ' . number_format($apartment->rent) . ' <small class="text-info">(Advance)</small>';
                                        $rentForCommission = 0; // No commission - money already accounted for
                                        $amountCoveringMonth = $apartment->rent;
                                        $statusDisplay = '<span class="badge bg-info">ADVANCE COVER</span>';
                                        $statusClass = 'text-info';
                                        $paymentDate = '<small class="text-info">Paid: ' . $advanceDate . '</small>';
                                        $monthsCovered = 1;
                                        
                                        // Calculate next payment
                                        if ($coveringAdvance->allocated_months) {
                                            $allocatedMonths = collect($coveringAdvance->allocated_months)
                                                ->sort()
                                                ->values();
                                            $lastCoveredMonth = Carbon::createFromFormat('Y-m', $allocatedMonths->last());
                                            if ($lastCoveredMonth->greaterThanOrEqualTo($reportData['month'])) {
                                                $nextPayment = $lastCoveredMonth->copy()->addMonth()->format('F Y');
                                            } else {
                                                $nextPayment = $reportData['month']->copy()->addMonth()->format('F Y');
                                            }
                                        } else {
                                            $nextPayment = $reportData['month']->copy()->addMonth()->format('F Y');
                                        }
                                        
                                        // For display amount
                                        $displayAmount = $apartment->rent;
                                    } 
                                    elseif ($totalAmountCoveringThisMonth > 0) {
                                        // Covered by payment made in a different month (not advance)
                                        $rentPaidDisplay = 'UGX ' . number_format($totalAmountCoveringThisMonth);
                                        $rentForCommission = 0; // No new money this month
                                        $amountCoveringMonth = $totalAmountCoveringThisMonth;
                                        
                                        if ($totalAmountCoveringThisMonth >= $apartment->rent) {
                                            $statusDisplay = '<span class="badge bg-success">' . $reportData['month']->format('F') . ' PAID</span>';
                                            $statusClass = '';
                                        } else {
                                            $statusDisplay = '<span class="badge bg-warning text-dark">PARTIAL - UGX ' . number_format($totalAmountCoveringThisMonth) . '</span>';
                                            $statusClass = 'text-warning';
                                        }
                                        
                                        $mostRecentPayment = $paymentsCoveringThisMonth->sortByDesc(function($payment) {
                                            return $payment->actual_payment_date ?? $payment->paid_at;
                                        })->first();
                                        
                                        $paymentDate = $mostRecentPayment->actual_payment_date 
                                            ? $mostRecentPayment->actual_payment_date->format('jS/m/Y')
                                            : $mostRecentPayment->paid_at->format('jS/m/Y');
                                        $nextPayment = $reportData['month']->copy()->addMonth()->format('F Y');
                                        $monthsCovered = 1;
                                    } 
                                    else {
                                        // UNPAID
                                        $rentPaidDisplay = 'UGX 0';
                                        $rentForCommission = 0;
                                        $amountCoveringMonth = 0;
                                        $statusDisplay = '<span class="badge bg-danger">UNPAID</span>';
                                        $statusClass = 'text-danger';
                                        $paymentDate = '-';
                                        $nextPayment = '<span class="badge bg-danger">UNPAID</span>';
                                        $monthsCovered = 0;
                                    }
                                    
                                    // Calculate commission
                                    $commission = $rentForCommission * ($reportData['landlord']->commission_rate / 100);
                                    $amountToLandlord = $rentForCommission - $commission;
                                    
                                    // Add to location totals (for commission calculation)
                                    $locationTotal += $rentForCommission;
                                    $locationCommission += $commission;
                                    
                                    // For display, show the amount actually paid this month OR amount covering month
                                    $displayAmount = ($totalAmountPaidThisMonth > 0) ? $totalAmountPaidThisMonth : $amountCoveringMonth;
                                @endphp
                                <tr>
                                    <td>{{ $apartment->number }}</td>
                                    <td>UGX {{ number_format($apartment->rent) }}</td>
                                    <td class="{{ $statusClass }}">
                                        {!! $rentPaidDisplay !!}
                                    </td>
                                    <td>UGX {{ number_format($commission) }}</td>
                                    <td>UGX {{ number_format($amountToLandlord) }}</td>
                                    <td>{!! $statusDisplay !!}</td>
                                    <td>{!! $paymentDate !!}</td>
                                    <td>{!! $nextPayment !!}</td>
                                </tr>
                            @endforeach
                            <!-- Location Totals -->
                            <tr class="table-warning fw-bold">
                                <td colspan="2">TOTAL {{ strtoupper($location) }}</td>
                                <td>UGX {{ number_format($locationTotal) }}</td>
                                <td>UGX {{ number_format($locationCommission) }}</td>
                                <td>UGX {{ number_format($locationTotal - $locationCommission) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach

            <!-- Expenses Section -->
            @if(isset($reportData['expenses']) && $reportData['expenses']->count() > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">EXPENSES FOR {{ strtoupper($reportData['month']->format('F Y')) }}</h6>
                            <a href="{{ route('admin.landlords.expenses.create', ['landlord' => $reportData['landlord']->id, 'month' => $reportData['month']->format('Y-m')]) }}"
                               class="btn btn-sm btn-dark">
                                <i class="bi bi-plus-circle"></i> Add Expense
                            </a>
                              <a href="{{ route('admin.landlords.expenses.index', $reportData['landlord']) }}?month={{ $reportData['month']->format('Y-m') }}"
                            class="btn btn-sm btn-outline-info">
                                <i class="bi bi-list-check"></i> View All Expenses
                            </a>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Category</th>
                                        <th>Apartment</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData['expenses'] as $expense)
                                    <tr>
                                        <td>{{ $expense->expense_date->format('M j, Y') }}</td>
                                        <td>{{ $expense->description }}</td>
                                        <td><span class="badge bg-secondary">{{ $expense->category_label }}</span></td>
                                        <td>{{ $expense->apartment ? $expense->apartment->number : 'General' }}</td>
                                        <td class="text-danger fw-bold">UGX {{ number_format($expense->amount) }}</td>
                                        <td>
                                            <a href="{{ route('admin.landlords.expenses.edit', ['landlord' => $reportData['landlord']->id, 'expense' => $expense->id]) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.landlords.expenses.destroy', ['landlord' => $reportData['landlord']->id, 'expense' => $expense->id]) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete this expense?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                    <tr class="table-warning fw-bold">
                                        <td colspan="4">TOTAL EXPENSES</td>
                                        <td class="text-danger">UGX {{ number_format($reportData['totalExpenses']) }}</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">No expenses recorded for this month</span>
                        <a href="{{ route('admin.landlords.expenses.create', ['landlord' => $reportData['landlord']->id, 'month' => $reportData['month']->format('Y-m')]) }}"
                           class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-plus-circle"></i> Add Expense
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Grand Totals -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>SUMMARY FOR {{ strtoupper($reportData['month']->format('F Y')) }}</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td>Total Rent Paid in {{ $reportData['month']->format('F') }}:</td>
                                    <td class="fw-bold">UGX {{ number_format($reportData['totalDisplayRent']) }}</td>
                                </tr>
                                <tr>
                                    <td>Commissionable Amount:</td>
                                    <td class="fw-bold">UGX {{ number_format($reportData['totalCommissionRent']) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Commission ({{ $reportData['landlord']->commission_rate }}%):</td>
                                    <td class="fw-bold text-danger">- UGX {{ number_format($reportData['totalCommission']) }}</td>
                                </tr>
                                @if(isset($reportData['totalExpenses']) && $reportData['totalExpenses'] > 0)
                                <tr>
                                    <td>Total Expenses Deducted:</td>
                                    <td class="fw-bold text-danger">- UGX {{ number_format($reportData['totalExpenses']) }}</td>
                                </tr>
                                @endif
                                <tr class="border-top">
                                    <td>Amount Due to Landlord:</td>
                                    <td class="fw-bold text-success fs-5">UGX {{ number_format($reportData['amountDue']) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>Payment Status:</td>
                                    <td class="fw-bold">
                                        @if($reportData['landlordPaymentStatus']['paid'])
                                            <span class="text-success">✓ PAID to Landlord</span>
                                            <br><small class="text-muted">Paid on: {{ \Carbon\Carbon::parse($reportData['landlordPaymentStatus']['paid_at'])->format('M j, Y g:i A') }}</small>
                                        @else
                                            <span class="text-warning">⏳ PENDING Payment</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection