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
        .status-advance { color: #28a745; font-weight: bold; }
        .status-partial { color: #ffc107; font-weight: bold; }
        .status-unpaid { color: #dc3545; font-weight: bold; }
        .status-vacant { color: #6c757d; font-weight: bold; }
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
                    $paymentStatus = $apartment->getPaymentStatusForReport($reportData['month']->format('Y-m'));
                    
                    // **NEW LOGIC: Get ALL payments made IN THIS CALENDAR MONTH (actual payment date)**
                    $actualPayments = $apartment->payments()
                        ->where('status', 'paid')
                        ->where(function($query) use ($reportData) {
                            $month = $reportData['month']->format('Y-m');
                            // Check actual payment date OR paid_at date
                            $query->whereRaw("DATE_FORMAT(actual_payment_date, '%Y-%m') = ?", [$month])
                                  ->orWhere(function($q) use ($month) {
                                      $q->whereNull('actual_payment_date')
                                        ->whereRaw("DATE_FORMAT(paid_at, '%Y-%m') = ?", [$month]);
                                  });
                        })
                        ->get();
                    
                    // **Use the SUM of ALL payments made this calendar month**
                    $rentPaid = $actualPayments->sum('amount');
                    
                    // Commission calculation on FULL amount paid
                    $commission = $rentPaid * ($reportData['landlord']->commission_rate / 100);
                    $amount = $rentPaid - $commission;
                    
                    // Add to location totals
                    $locationTotal += $rentPaid;
                    $locationCommission += $commission;
                    $locationAmount += $amount;
                    
                    // Determine if this is an advance payment (multiple months)
                    $isAdvancePayment = $rentPaid > $apartment->rent;
                    $monthsCovered = $isAdvancePayment ? floor($rentPaid / $apartment->rent) : 0;
                    
                    // Get the payment for display
                    $displayPayment = $actualPayments->first();
                @endphp
                <tr>
                    <td>{{ $apartment->number }}</td>
                    <td>UGX {{ number_format($apartment->rent) }}</td>
                    
                    <!-- Rent Paid Column -->
                    <td>
                        @if($rentPaid == 0)
                            UGX 0
                        @else
                            UGX {{ number_format($rentPaid) }}
                            @if($isAdvancePayment)
                                <div class="small-note">
                                    ({{ $monthsCovered }} month{{ $monthsCovered > 1 ? 's' : '' }})
                                </div>
                            @endif
                        @endif
                    </td>
                    
                    <td>UGX {{ number_format($commission) }}</td>
                    <td>UGX {{ number_format($amount) }}</td>
                    
                    <!-- Month/s Paid Column -->
                    <td>
                        @if(!$apartment->tenant)
                            <span class="badge badge-secondary">VACANT</span>
                        @elseif($rentPaid == 0)
                            <span class="badge badge-danger">UNPAID</span>
                        @elseif($isAdvancePayment)
                            <span class="badge badge-success">
                                {{ $monthsCovered }} MONTH{{ $monthsCovered > 1 ? 'S' : '' }} ADVANCE PAID
                            </span>
                        @elseif($rentPaid >= $apartment->rent)
                            <span class="badge badge-success">{{ $reportData['month']->format('F') }} PAID</span>
                        @else
                            <span class="badge badge-warning">PARTIAL - UGX {{ number_format($rentPaid) }}</span>
                        @endif
                    </td>
                    
                    <!-- Date of Payment Column -->
                    <td>
                        @if($displayPayment)
                            {{ $displayPayment->actual_payment_date ? $displayPayment->actual_payment_date->format('jS/m/Y') : $displayPayment->created_at->format('jS/m/Y') }}
                            @if($displayPayment->is_advance_payment)
                                <div class="advance-note">(Advance Payment)</div>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    
                    <!-- Next of Payment Column -->
                    <td>
                        @if(!$apartment->tenant)
                            <span class="badge badge-secondary">VACANT</span>
                        @elseif($rentPaid == 0)
                            <span class="badge badge-danger">UNPAID</span>
                        @elseif($isAdvancePayment)
                            {{ $reportData['month']->copy()->addMonths($monthsCovered)->format('F Y') }}
                        @elseif($rentPaid >= $apartment->rent)
                            {{ $reportData['month']->copy()->addMonth()->format('F') }}
                        @else
                            {{ $reportData['month']->copy()->addMonth()->format('F') }}
                        @endif
                    </td>
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
        
        <!-- Payment Status in PDF -->
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