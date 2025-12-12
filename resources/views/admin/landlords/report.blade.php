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
                            $reportMonth = $reportData['month']->format('Y-m');
                            
                            // =====================================================
                            // KEY: Get ACTUAL amount paid in this calendar month
                            // This is the money we calculate commission on
                            // =====================================================
                            $actualAmountThisMonth = $apartment->getActualAmountPaidInMonth($reportMonth);
                            
                            // Check if this month is covered by a PREVIOUS advance
                            $coveringAdvance = $apartment->getCoveringAdvancePayment($reportMonth);
                            $coveredByPreviousAdvance = ($coveringAdvance !== null);
                            
                            // Get payments actually made this month
                            $paymentsThisMonth = $apartment->getPaymentsActuallyMadeInMonth($reportMonth);
                            $advancePaymentThisMonth = $paymentsThisMonth->where('is_advance_payment', true)->first();
                            
                            // Determine what to show
                            if (!$apartment->tenant) {
                                // VACANT
                                $rentPaidDisplay = 'UGX 0';
                                $rentForCommission = 0;
                                $statusDisplay = '<span class="badge bg-secondary">VACANT</span>';
                                $statusClass = '';
                                $nextPayment = '<span class="badge bg-secondary">VACANT</span>';
                                $paymentDate = '-';
                            } elseif ($coveredByPreviousAdvance && $actualAmountThisMonth == 0) {
                                // Covered by PREVIOUS advance - NO commission (landlord already paid)
                                $rentPaidDisplay = '<span class="text-info fw-bold">ADVANCE</span>';
                                $rentForCommission = 0; // KEY: Zero commission!
                                $statusDisplay = '<span class="badge bg-info">COVERED BY ADVANCE</span>';
                                $statusClass = 'text-info';
                                
                                $advanceDate = $coveringAdvance->actual_payment_date 
                                    ? $coveringAdvance->actual_payment_date->format('jS/m/Y')
                                    : $coveringAdvance->paid_at->format('jS/m/Y');
                                $paymentDate = '<small class="text-info">Adv: ' . $advanceDate . '</small>';
                                
                                $allocatedMonths = $coveringAdvance->allocated_months ?? [];
                                if (count($allocatedMonths) > 0) {
                                    $lastMonth = \Carbon\Carbon::createFromFormat('Y-m', max($allocatedMonths));
                                    $nextPayment = $lastMonth->addMonth()->format('F Y');
                                } else {
                                    $nextPayment = $reportData['month']->copy()->addMonth()->format('F Y');
                                }
                            } elseif ($advancePaymentThisMonth) {
                                // ADVANCE payment made THIS month - full amount for commission
                                $rentPaidDisplay = 'UGX ' . number_format($actualAmountThisMonth);
                                $rentForCommission = $actualAmountThisMonth;
                                
                                $monthsCovered = count($advancePaymentThisMonth->allocated_months ?? []);
                                if ($monthsCovered == 0) {
                                    $monthsCovered = floor($actualAmountThisMonth / $apartment->rent);
                                }
                                
                                $statusDisplay = '<span class="badge bg-success">' . $monthsCovered . ' MONTH' . ($monthsCovered > 1 ? 'S' : '') . ' ADVANCE PAID</span>';
                                $statusClass = 'text-success fw-bold';
                                
                                $paymentDate = $advancePaymentThisMonth->actual_payment_date 
                                    ? $advancePaymentThisMonth->actual_payment_date->format('jS/m/Y')
                                    : $advancePaymentThisMonth->paid_at->format('jS/m/Y');
                                
                                $allocatedMonths = $advancePaymentThisMonth->allocated_months ?? [];
                                if (count($allocatedMonths) > 0) {
                                    $lastMonth = \Carbon\Carbon::createFromFormat('Y-m', max($allocatedMonths));
                                    $nextPayment = $lastMonth->addMonth()->format('F Y');
                                } else {
                                    $nextPayment = $reportData['month']->copy()->addMonths($monthsCovered)->format('F Y');
                                }
                            } elseif ($actualAmountThisMonth > 0) {
                                // Regular payment made this month
                                $rentPaidDisplay = 'UGX ' . number_format($actualAmountThisMonth);
                                $rentForCommission = $actualAmountThisMonth;
                                
                                if ($actualAmountThisMonth >= $apartment->rent) {
                                    $statusDisplay = '<span class="badge bg-success">' . $reportData['month']->format('F') . ' PAID</span>';
                                    $statusClass = '';
                                } else {
                                    $statusDisplay = '<span class="badge bg-warning text-dark">PARTIAL - UGX ' . number_format($actualAmountThisMonth) . '</span>';
                                    $statusClass = 'text-warning';
                                }
                                
                                $firstPayment = $paymentsThisMonth->first();
                                $paymentDate = $firstPayment->actual_payment_date 
                                    ? $firstPayment->actual_payment_date->format('jS/m/Y')
                                    : $firstPayment->paid_at->format('jS/m/Y');
                                
                                $nextPayment = $reportData['month']->copy()->addMonth()->format('F Y');
                            } else {
                                // UNPAID
                                $rentPaidDisplay = 'UGX 0';
                                $rentForCommission = 0;
                                $statusDisplay = '<span class="badge bg-danger">UNPAID</span>';
                                $statusClass = 'text-danger';
                                $paymentDate = '-';
                                $nextPayment = '<span class="badge bg-danger">UNPAID</span>';
                            }
                            
                            // Calculate commission on ACTUAL money received this month
                            $commission = $rentForCommission * ($reportData['landlord']->commission_rate / 100);
                            $amountToLandlord = $rentForCommission - $commission;
                            
                            // Add to totals
                            $locationTotal += $rentForCommission;
                            $locationCommission += $commission;
                        @endphp
                        <tr>
                            <td>{{ $apartment->number }}</td>
                            <td>UGX {{ number_format($apartment->rent) }}</td>
                            <td class="{{ $statusClass }}">{!! $rentPaidDisplay !!}</td>
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