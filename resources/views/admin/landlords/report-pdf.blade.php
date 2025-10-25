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
        .client-info { 
            margin-bottom: 15px; 
        }
        .client-info p { 
            margin: 2px 0;
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
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>RENT TODAY - MANAGEMENT AGENCY</h1>
        <h2>CLIENT'S MONTHLY REPORT FOR THE MONTH OF: {{ strtoupper($reportData['month']->format('F Y')) }}</h2>
        <p><strong>CLIENT'S NAME:</strong> {{ strtoupper($reportData['landlord']->name) }}</p>
        <p><strong>PROPERTY LOCATION:</strong> {{ $locations->implode(' and ') }}</p>
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
                    // FIX: Use the same logic as web view - get current month payment
                    $payment = $apartment->payments->first(); // This gets the first payment for the month
                    $rentPaid = $payment ? $payment->amount : 0;
                    $commission = $payment ? ($rentPaid * ($reportData['landlord']->commission_rate / 100)) : 0;
                    $amount = $rentPaid - $commission;
                    
                    $locationTotal += $rentPaid;
                    $locationCommission += $commission;
                    $locationAmount += $amount;
                @endphp
                <tr>
                    <td>{{ $apartment->number }}</td>
                    <td>UGX {{ number_format($apartment->rent) }}</td>
                    <td>UGX {{ number_format($rentPaid) }}</td>
                    <td>UGX {{ number_format($commission) }}</td>
                    <td>UGX {{ number_format($amount) }}</td>
                    <td>{{ $payment ? $reportData['month']->format('F') : 'UNPAID' }}</td>
                    <td>
                        @if($payment && $payment->created_at)
                            {{ $payment->created_at->format('jS/m/Y') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $reportData['month']->copy()->addMonth()->format('F') }}</td>
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