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
                    $locationTotalValue = 0;
                    $locationAgencyTotal = 0;
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
                                    $rowData = $apartment->getReportRowData($reportMonth);
                                    
                                    $commission = $rowData['commissionable_amount'] * ($reportData['landlord']->commission_rate / 100);
                                    
                                    // Row-level payout: Only if agency collected more than commission
                                    // If direct, this will be 0, and the debt is handled in the summary
                                    $amountToLandlord = max(0, $rowData['rent_collected_agency'] - $commission);
                                    
                                    // Add to location totals
                                    $locationTotalValue += $rowData['commissionable_amount']; // Total economic value
                                    $locationAgencyTotal += $rowData['rent_collected_agency']; // Actual cash collected
                                    $locationCommission += $commission;
                                    
                                    $rentPaidDisplay = 'UGX ' . number_format($rowData['rent_collected_agency']);
                                    $statusClass = '';
                                    
                                    if ($rowData['is_covered_by_advance']) {
                                        $rentPaidDisplay = 'UGX ' . number_format($apartment->rent) . ' <small class="text-info">(Advance)</small>';
                                        $statusClass = 'text-info';
                                    } elseif ($rowData['rent_collected_agency'] == 0 && $rowData['commissionable_amount'] > 0) {
                                        $rentPaidDisplay = 'UGX 0';
                                        if ($rowData['status_label'] === 'UNPAID') $statusClass = 'text-danger';
                                    } elseif ($rowData['commissionable_amount'] == 0) {
                                        $rentPaidDisplay = 'UGX 0';
                                        if ($rowData['status_label'] === 'UNPAID') $statusClass = 'text-danger';
                                    }
                                    
                                    if ($rowData['rent_paid_direct'] > 0) {
                                        $rentPaidDisplay .= '<br><small class="text-info">Direct to Landlord: UGX ' . number_format($rowData['rent_paid_direct']) . '</small>';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $apartment->number }}</td>
                                    <td>UGX {{ number_format($apartment->rent) }}</td>
                                    <td class="{{ $statusClass }}">
                                        {!! $rentPaidDisplay !!}
                                    </td>
                                    <td>UGX {{ number_format($commission) }}</td>
                                    <td>UGX {{ number_format($amountToLandlord) }}</td>
                                    <td>
                                        <span class="badge {{ $rowData['status_label'] === 'PAID' || str_contains($rowData['status_label'], 'ADVANCE') ? 'bg-success' : ($rowData['status_label'] === 'UNPAID' ? 'bg-danger' : 'bg-info') }}">
                                            {{ $rowData['status_label'] }}
                                        </span>
                                    </td>
                                    <td>{!! $rowData['payment_date'] !!}</td>
                                    <td>{!! $rowData['next_payment'] !!}</td>
                                </tr>
                            @endforeach
                            <!-- Location Totals -->
                            <tr class="table-warning fw-bold">
                                <td colspan="2">TOTAL {{ strtoupper($location) }}</td>
                                <td>UGX {{ number_format($locationAgencyTotal) }}</td>
                                <td>UGX {{ number_format($locationCommission) }}</td>
                                <td>UGX {{ number_format(max(0, $locationAgencyTotal - $locationCommission)) }}</td>
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
                                    <td>Total Rent Collected by Agency:</td>
                                    <td class="fw-bold">UGX {{ number_format($reportData['totalCollectedRent']) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Rent Value (Incl. Total Paid):</td>
                                    <td class="fw-bold">UGX {{ number_format($reportData['totalDisplayRent']) }}</td>
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
                                    @if($reportData['amountDue'] >= 0)
                                        <td class="fs-5">Amount Due to Landlord:</td>
                                        <td class="fw-bold text-success fs-5">UGX {{ number_format($reportData['amountDue']) }}</td>
                                    @else
                                        <td class="fs-5 text-danger">Commission Owed by Landlord:</td>
                                        <td class="fw-bold text-danger fs-5">UGX {{ number_format(abs($reportData['amountDue'])) }}</td>
                                    @endif
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