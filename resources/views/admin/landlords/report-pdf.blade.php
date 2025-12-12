<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>RENT TODAY - Monthly Report</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 11px; 
            margin: 15px;
            line-height: 1.2;
        }
        .header { 
            text-align: center; 
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 { 
            margin: 0; 
            font-size: 18px; 
            font-weight: bold;
        }
        .header h2 { 
            margin: 3px 0; 
            font-size: 14px; 
        }
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0;
            font-size: 9px;
        }
        .table th, .table td { 
            border: 1px solid #000; 
            padding: 4px 3px; 
            text-align: left;
        }
        .table th { 
            background-color: #f0f0f0; 
            font-weight: bold;
            text-align: center;
        }
        .location-header { 
            background-color: #333; 
            color: white; 
            padding: 6px;
            margin: 15px 0 8px 0;
            font-weight: bold;
            font-size: 11px;
        }
        .summary { 
            margin-top: 20px; 
            padding: 12px; 
            background-color: #f8f9fa;
            border: 1px solid #ddd;
        }
        .total-row {
            background-color: #fff3cd;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .footer { 
            margin-top: 20px; 
            padding-top: 8px; 
            border-top: 1px solid #ccc;
            font-size: 9px;
        }
        .small-note { 
            font-size: 7px; 
            color: #666; 
            line-height: 1.1;
        }
        .advance-note {
            font-size: 7px;
            color: #17a2b8;
            font-style: italic;
        }
        .badge {
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success { background-color: #28a745; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }
        .badge-warning { background-color: #ffc107; color: black; }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-secondary { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>RENT TODAY - MANAGEMENT AGENCY</h1>
        <h2>CLIENT'S MONTHLY REPORT FOR THE MONTH OF: {{ strtoupper($reportData['month']->format('F Y')) }}</h2>
        <p><strong>CLIENT'S NAME:</strong> {{ strtoupper($reportData['landlord']->name) }}</p>
        <p><strong>PROPERTY LOCATION:</strong> {{ $locations->implode(' and ') }}</p>
        <p><strong>COMMISSION RATE:</strong> {{ $reportData['landlord']->commission_rate }}%</p>
        
        <!-- Payment Status -->
        <p><strong>PAYMENT STATUS:</strong> 
            @if($reportData['landlordPaymentStatus']['paid'])
                <strong style="color: #28a745;">PAID TO LANDLORD</strong>
                ({{ \Carbon\Carbon::parse($reportData['landlordPaymentStatus']['paid_at'])->format('M j, Y') }})
            @else
                <strong style="color: #ffc107;">PENDING PAYMENT</strong>
            @endif
        </p>
    </div>

    <!-- Apartments by Location -->
    @foreach($locations as $location)
        @php
            $locationApartments = $reportData['apartments']->where('location', $location);
            $locationTotal = 0;
            $locationCommission = 0;
            $locationAmount = 0;
        @endphp
        
        <div class="location-header">
            {{ strtoupper($location) }}
        </div>
        
        <table class="table">
            <thead>
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
                    
                    // Get ACTUAL amount paid in this calendar month
                    $actualAmountThisMonth = $apartment->getActualAmountPaidInMonth($reportMonth);
                    
                    // Check if covered by PREVIOUS advance
                    $coveringAdvance = $apartment->getCoveringAdvancePayment($reportMonth);
                    $coveredByPreviousAdvance = ($coveringAdvance !== null);
                    
                    // Get payments made this month
                    $paymentsThisMonth = $apartment->getPaymentsActuallyMadeInMonth($reportMonth);
                    $advancePaymentThisMonth = $paymentsThisMonth->where('is_advance_payment', true)->first();
                    
                    // Determine display
                    if (!$apartment->tenant) {
                        $rentPaidDisplay = 'UGX 0';
                        $rentForCommission = 0;
                        $statusDisplay = '<span class="badge badge-secondary">VACANT</span>';
                        $nextPayment = '<span class="badge badge-secondary">VACANT</span>';
                        $paymentDate = '-';
                    } elseif ($coveredByPreviousAdvance && $actualAmountThisMonth == 0) {
                        // Covered by PREVIOUS advance - NO commission
                        $rentPaidDisplay = '<strong style="color: #17a2b8;">ADVANCE</strong>';
                        $rentForCommission = 0;
                        $statusDisplay = '<span class="badge badge-info">COVERED BY ADVANCE</span>';
                        
                        $advanceDate = $coveringAdvance->actual_payment_date 
                            ? $coveringAdvance->actual_payment_date->format('jS/m/Y')
                            : $coveringAdvance->paid_at->format('jS/m/Y');
                        $paymentDate = '<small style="color: #17a2b8;">Adv: ' . $advanceDate . '</small>';
                        
                        $allocatedMonths = $coveringAdvance->allocated_months ?? [];
                        if (count($allocatedMonths) > 0) {
                            $lastMonth = \Carbon\Carbon::createFromFormat('Y-m', max($allocatedMonths));
                            $nextPayment = $lastMonth->addMonth()->format('F Y');
                        } else {
                            $nextPayment = $reportData['month']->copy()->addMonth()->format('F Y');
                        }
                    } elseif ($advancePaymentThisMonth) {
                        // ADVANCE payment made THIS month
                        $rentPaidDisplay = 'UGX ' . number_format($actualAmountThisMonth);
                        $rentForCommission = $actualAmountThisMonth;
                        
                        $monthsCovered = count($advancePaymentThisMonth->allocated_months ?? []);
                        if ($monthsCovered == 0) {
                            $monthsCovered = floor($actualAmountThisMonth / $apartment->rent);
                        }
                        
                        $statusDisplay = '<span class="badge badge-success">' . $monthsCovered . ' MONTH' . ($monthsCovered > 1 ? 'S' : '') . ' ADVANCE</span>';
                        
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
                        // Regular payment
                        $rentPaidDisplay = 'UGX ' . number_format($actualAmountThisMonth);
                        $rentForCommission = $actualAmountThisMonth;
                        
                        if ($actualAmountThisMonth >= $apartment->rent) {
                            $statusDisplay = '<span class="badge badge-success">' . $reportData['month']->format('F') . ' PAID</span>';
                        } else {
                            $statusDisplay = '<span class="badge badge-warning">PARTIAL</span>';
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
                        $statusDisplay = '<span class="badge badge-danger">UNPAID</span>';
                        $paymentDate = '-';
                        $nextPayment = '<span class="badge badge-danger">UNPAID</span>';
                    }
                    
                    // Commission on ACTUAL money received
                    $commission = $rentForCommission * ($reportData['landlord']->commission_rate / 100);
                    $amountToLandlord = $rentForCommission - $commission;
                    
                    $locationTotal += $rentForCommission;
                    $locationCommission += $commission;
                    $locationAmount += $amountToLandlord;
                @endphp
                <tr>
                    <td>{{ $apartment->number }}</td>
                    <td>UGX {{ number_format($apartment->rent) }}</td>
                    <td>{!! $rentPaidDisplay !!}</td>
                    <td>UGX {{ number_format($commission) }}</td>
                    <td>UGX {{ number_format($amountToLandlord) }}</td>
                    <td>{!! $statusDisplay !!}</td>
                    <td>{!! $paymentDate !!}</td>
                    <td>{!! $nextPayment !!}</td>
                </tr>
                @endforeach
                
                <!-- Location Totals -->
                <tr class="total-row">
                    <td colspan="2"><strong>TOTAL {{ strtoupper($location) }}</strong></td>
                    <td><strong>UGX {{ number_format($locationTotal) }}</strong></td>
                    <td><strong>UGX {{ number_format($locationCommission) }}</strong></td>
                    <td><strong>UGX {{ number_format($locationAmount) }}</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <!-- Grand Totals -->
    <div class="summary">
        <h4>SUMMARY FOR {{ strtoupper($reportData['month']->format('F Y')) }}</h4>
        <table style="width: 100%;">
            <tr>
                <td><strong>Total Rent Collected:</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($reportData['totalRent']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Commission ({{ $reportData['landlord']->commission_rate }}%):</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($reportData['totalCommission']) }}</strong></td>
            </tr>
            <tr>
                <td><strong>Amount Due to Landlord:</strong></td>
                <td class="text-right"><strong>UGX {{ number_format($reportData['amountDue']) }}</strong></td>
            </tr>
        </table>
        
        <table style="width: 100%; margin-top: 10px;">
            <tr>
                <td><strong>Payment Status:</strong></td>
                <td class="text-right">
                    @if($reportData['landlordPaymentStatus']['paid'])
                        <strong style="color: #28a745;">✓ PAID TO LANDLORD</strong>
                        <br><small style="color: #666;">Paid on: {{ \Carbon\Carbon::parse($reportData['landlordPaymentStatus']['paid_at'])->format('M j, Y g:i A') }}</small>
                    @else
                        <strong style="color: #ffc107;">⏳ PENDING PAYMENT</strong>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td>
                    <strong>Generated On:</strong> {{ now()->format('F j, Y g:i A') }}
                </td>
                <td class="text-right">
                    <strong>RENT TODAY MANAGEMENT AGENCY</strong><br>
                    Professional Property Management
                </td>
            </tr>
        </table>
    </div>
</body>
</html>