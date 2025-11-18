@extends('admin.layouts.app')

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
            <!-- Rest of your existing report content remains exactly the same -->
            @foreach($locations as $location)
            @php
                $locationApartments = $reportData['apartments']->where('location', $location);
                $locationTotal = 0;
                $locationCommission = 0;
            @endphp
            
            <h5 class="mt-4">{{ strtoupper($location) }}</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Apartment No</th>
                            <th>Month Rate</th>
                            <th>Rent Paid</th>
                            <th>Commission</th>
                            <th>Amount</th>
                            <th>Month/s Paid</th>
                            <th>Date of payment</th>
                            <th>Next of pyt</th>
                        </tr>
                    </thead>
                    <tbody>
        @foreach($locationApartments as $apartment)
@php
    $paymentStatus = $apartment->getPaymentStatusForReport($reportData['month']->format('Y-m'));
    $rentPaid = $paymentStatus['amount_paid']; // Will be 0 for advance months
    
    // Only calculate commission if money was actually paid this month
    $commission = $rentPaid > 0 ? ($rentPaid * ($reportData['landlord']->commission_rate / 100)) : 0;
    $amount = $rentPaid - $commission;
    
    $locationTotal += $rentPaid; // Will only add when money was actually paid
    $locationCommission += $commission;
    
    // Enhanced status display
    if ($paymentStatus['status'] === 'VACANT') {
        $statusDisplay = '<span class="badge bg-secondary">VACANT</span>';
        $statusClass = '';
        $rentPaidDisplay = 'UGX 0';
    } elseif ($paymentStatus['status'] === 'PAID') {
        if ($paymentStatus['is_advance'] && !$paymentStatus['payment_made_this_month']) {
            // This month is covered by advance, but payment wasn't made this month
            $statusDisplay = '<span class="badge bg-success">ADVANCE (' . $paymentStatus['months_covered'] . ' REMAINING)</span>';
            $statusClass = 'text-success fw-bold';
            $rentPaidDisplay = '<span class="text-success fw-bold">ADVANCE</span>'; // Show ADVANCE instead of money
        } elseif ($paymentStatus['is_advance'] && $paymentStatus['payment_made_this_month']) {
            // Payment was made this month and covers multiple months
            $statusDisplay = '<span class="badge bg-success">' . $paymentStatus['months_covered'] . ' MONTH' . ($paymentStatus['months_covered'] > 1 ? 'S' : '') . ' ADVANCE</span>';
            $statusClass = 'text-success fw-bold';
            $rentPaidDisplay = 'UGX ' . number_format($rentPaid); // Show actual amount paid
        } elseif ($paymentStatus['is_partial']) {
            $statusDisplay = '<span class="badge bg-warning text-dark">PARTIAL</span>';
            $statusClass = 'text-warning';
            $rentPaidDisplay = 'UGX ' . number_format($rentPaid);
        } else {
            $statusDisplay = '<span class="badge bg-success">' . $reportData['month']->format('F') . '</span>';
            $statusClass = '';
            $rentPaidDisplay = 'UGX ' . number_format($rentPaid);
        }
    } else {
        $statusDisplay = '<span class="badge bg-danger">UNPAID</span>';
        $statusClass = 'text-danger';
        $rentPaidDisplay = 'UGX 0';
    }
    
    // Get the payment that's allocated to this month (based on 'month' field)
    $displayPayment = $apartment->getPaymentForMonth($reportData['month']->format('Y-m'));
@endphp
<tr>
    <td>{{ $apartment->number }}</td>
    <td>UGX {{ number_format($apartment->rent) }}</td>
    <td class="{{ $statusClass }}">{!! $rentPaidDisplay !!}</td>
    <td>UGX {{ number_format($commission) }}</td>
    <td>UGX {{ number_format($amount) }}</td>
    <td>{!! $statusDisplay !!}</td>
    <td>
    @if($displayPayment && $displayPayment->actual_payment_date)
        {{ $displayPayment->actual_payment_date->format('jS/m/Y') }}
        @if($displayPayment->actual_payment_date->format('Y-m-d') != $displayPayment->created_at->format('Y-m-d'))
            <br><small class="text-muted">(recorded: {{ $displayPayment->created_at->format('jS/m/Y') }})</small>
        @endif
    @elseif($displayPayment)
        {{ $displayPayment->created_at->format('jS/m/Y') }}
    @else
        -
    @endif
</td>
    <td>
        @if($paymentStatus['is_advance'] && $paymentStatus['months_covered'] > 0)
            {{ $reportData['month']->copy()->addMonths($paymentStatus['months_covered'])->format('F Y') }}
        @else
            {{ $reportData['month']->copy()->addMonth()->format('F') }}
        @endif
    </td>
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

            <!-- Grand Totals -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>SUMMARY FOR {{ strtoupper($reportData['month']->format('F Y')) }}</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td>Total Rent Collected:</td>
                                    <td class="fw-bold">UGX {{ number_format($reportData['totalRent']) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Commission ({{ $reportData['landlord']->commission_rate }}%):</td>
                                    <td class="fw-bold">UGX {{ number_format($reportData['totalCommission']) }}</td>
                                </tr>
                                <tr class="border-top">
                                    <td>Amount Due to Landlord:</td>
                                    <td class="fw-bold text-success">UGX {{ number_format($reportData['amountDue']) }}</td>
                                </tr>
                                <!-- Payment Status Summary -->
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